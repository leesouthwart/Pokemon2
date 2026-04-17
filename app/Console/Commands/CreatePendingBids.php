<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;
use App\Models\PendingBid;
use App\Services\EbayService;
use Carbon\Carbon;

class CreatePendingBids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pending:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find cards with PSA titles and create pending bids for matching eBay listings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pending bids creation...');

        // Check user balance - don't create pending bids if balance is below $200
        $user = \App\Models\User::where('email', 'leesouthwart@gmail.com')->first();
        
        if (!$user) {
            $this->error('User not found');
            return Command::FAILURE;
        }

        if ($user->balance < 100) {
            $this->warn("User balance ({$user->balance}) is below minimum threshold of $200. Skipping pending bid creation.");
            return Command::SUCCESS;
        }

        // Get cards with PSA titles that aren't excluded
        $cards = Card::whereNotNull('psa_title')
            ->where('excluded_from_sniping', false)
            ->get();

        $this->info("Found {$cards->count()} cards with PSA titles");

        $ebayService = new EbayService();
        $listings = $ebayService->getPsaJapanesePsa10Auctions();

        $this->info("Found " . count($listings) . " eBay listings");

        $pendingBidsCreated = 0;
        $pendingBidsUpdated = 0;

        foreach ($listings as $listing) {
            // Find matching card by PSA title
            $card = Card::findByPsaTitle($listing['title']);

            if (!$card) {
                continue;
            }

            // Check if pending bid already exists for this eBay item
            $existingPendingBid = PendingBid::where('ebay_item_id', $listing['itemId'])
                ->first();

            if ($existingPendingBid) {
                // Update existing pending bid with latest info
                $existingPendingBid->update([
                    'ebay_title' => $listing['title'],
                    'ebay_image_url' => $listing['image'] ?? null,
                    'ebay_url' => $listing['url'] ?? '',
                    'current_bid' => $listing['currentBid'] ?? 0,
                    'end_date' => isset($listing['endDate']) ? Carbon::parse($listing['endDate']) : null,
                ]);
                $pendingBidsUpdated++;
                continue;
            }

            // Calculate bid amount:
            // 1) Prefer PSA listing-based bid (15% target profit after fees) when at least 3 listings exist
            // 2) Fall back to buy+grade bid price when listings are sparse
            $bidAmount = $this->calculateBidAmount($card, $ebayService);

            if ($bidAmount <= 0) {
                $this->warn("Card {$card->id} has invalid bid price, skipping");
                continue;
            }

            // Only create if bid amount is higher than current bid
            if ($bidAmount <= ($listing['currentBid'] ?? 0)) {
                $this->warn("Bid amount {$bidAmount} is not higher than current bid " . ($listing['currentBid'] ?? 0) . " for item {$listing['itemId']}, skipping");
                continue;
            }

            // Create pending bid
            PendingBid::create([
                'card_id' => $card->id,
                'ebay_item_id' => $listing['itemId'],
                'ebay_title' => $listing['title'],
                'ebay_image_url' => $listing['image'] ?? null,
                'ebay_url' => $listing['url'] ?? '',
                'current_bid' => $listing['currentBid'] ?? 0,
                'bid_amount' => $bidAmount,
                'currency' => $listing['currency'] ?? 'USD',
                'end_date' => isset($listing['endDate']) ? Carbon::parse($listing['endDate']) : null,
                'bid_submitted' => false,
            ]);

            $pendingBidsCreated++;
        }

        $this->info("Created {$pendingBidsCreated} new pending bids");
        $this->info("Updated {$pendingBidsUpdated} existing pending bids");

        return Command::SUCCESS;
    }

    /**
     * Calculate bid amount for a card.
     *
     * If there are at least 3 PSA 10 Buy It Now listings, use the lowest listing
     * to derive a bid that targets 15% profit after selling fees.
     * Otherwise, fall back to card buy+grade based bid price.
     */
    private function calculateBidAmount(Card $card, EbayService $ebayService): float
    {
        $fallbackBidAmount = (float) $card->getBidPrice();

        $searchTerms = [$card->search_term];
        $card->load('psaTitles');
        foreach ($card->psaTitles as $psaTitle) {
            $searchTerms[] = $psaTitle->title;
        }
        $searchTerms = array_values(array_unique(array_filter($searchTerms)));

        // Merge results across search variants; count unique items only. Summing per-term
        // counts inflates depth by counting the same inventory many times.
        $byItemId = [];
        foreach ($searchTerms as $searchTerm) {
            $listings = $ebayService->searchPsa10BuyItNow($searchTerm);
            foreach ($listings as $row) {
                $itemId = $row['itemId'] ?? '';
                if ($itemId === '') {
                    continue;
                }
                $price = (float) ($row['price'] ?? 0);
                // Ignore rows with no usable price so a $0 parse does not wipe listing-based math.
                if ($price <= 0) {
                    continue;
                }
                if (!isset($byItemId[$itemId]) || $price < $byItemId[$itemId]) {
                    $byItemId[$itemId] = $price;
                }
            }
        }

        $uniqueListingCount = count($byItemId);
        $lowestPrice = null;
        foreach ($byItemId as $price) {
            if ($lowestPrice === null || $price < $lowestPrice) {
                $lowestPrice = $price;
            }
        }

        if ($uniqueListingCount >= 3 && $lowestPrice !== null && $lowestPrice > 0) {
            $listingBasedBid = $this->calculateBidFromTargetProfit($lowestPrice, 0.15);
            if ($listingBasedBid > 0) {
                $this->info("Card {$card->id} using listing-based bid {$listingBasedBid} (lowest PSA 10 listing: \${$lowestPrice}, unique listings: {$uniqueListingCount})");
                return $listingBasedBid;
            }
        }

        $this->info("Card {$card->id} using fallback buy+grade bid {$fallbackBidAmount} (unique PSA listings: {$uniqueListingCount})");
        return $fallbackBidAmount;
    }

    /**
     * Derive max bid from expected sale price to preserve target profit after fees.
     */
    private function calculateBidFromTargetProfit(float $expectedSalePrice, float $targetProfitMargin): float
    {
        $netAfterFees = $this->calculateNetSaleAfterFees($expectedSalePrice);
        if ($netAfterFees <= 0) {
            return 0;
        }

        // targetProfitMargin = (netAfterFees - bid) / bid
        // => bid = netAfterFees / (1 + targetProfitMargin)
        $maxBid = $netAfterFees / (1 + $targetProfitMargin);

        // Keep bids conservative and whole-dollar for sniping.
        return (float) floor($maxBid);
    }

    /**
     * Existing fee model used in profitability checks:
     * - 13% platform fees
     * - Additional $3 on lower-priced sales (< $100)
     */
    private function calculateNetSaleAfterFees(float $salePrice): float
    {
        $net = $salePrice * 0.87;

        if ($salePrice < 100) {
            $net -= 3;
        }

        return $net;
    }
}


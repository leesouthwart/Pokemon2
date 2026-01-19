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

            // Calculate bid amount
            $bidAmount = $card->getBidPrice();

            if ($bidAmount <= 0) {
                $this->warn("Card {$card->id} has invalid bid price, skipping");
                continue;
            }

            // Only create if bid amount is higher than current bid
            if ($bidAmount <= ($listing['currentBid'] ?? 0)) {
                $this->warn("Bid amount {$bidAmount} is not higher than current bid " . ($listing['currentBid'] ?? 0) . " for item {$listing['itemId']}, skipping");
                continue;
            }

            // Profitability check: Search for Buy It Now listings and verify profitability
            $isProfitable = $this->checkProfitability($card, $bidAmount, $ebayService);
            
            if (!$isProfitable) {
                $this->warn("Skipping card {$card->id}: Not profitable based on current Buy It Now prices");
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
     * Check if a bid is profitable by searching for Buy It Now listings
     * 
     * @param Card $card The card to check
     * @param float $bidAmount The bid amount to check profitability for
     * @param EbayService $ebayService The eBay service instance
     * @return bool True if profitable, false otherwise
     */
    private function checkProfitability(Card $card, float $bidAmount, EbayService $ebayService): bool
    {
        // Get all search terms: card search_term + all PSA titles
        $searchTerms = [$card->search_term];
        
        // Add all PSA titles linked to this card
        $card->load('psaTitles');
        foreach ($card->psaTitles as $psaTitle) {
            $searchTerms[] = $psaTitle->title;
        }

        $lowestPrice = null;

        // Search for each term and find the lowest price
        foreach ($searchTerms as $searchTerm) {
            $listings = $ebayService->searchPsa10BuyItNow($searchTerm);
            
            if (!empty($listings)) {
                // Listings are already sorted by price ascending
                $firstListingPrice = $listings[0]['price'];
                
                if ($lowestPrice === null || $firstListingPrice < $lowestPrice) {
                    $lowestPrice = $firstListingPrice;
                }
            }
        }

        // If no listings found, assume profitable (proceed with bid)
        if ($lowestPrice === null) {
            return true;
        }

        // Calculate profitability:
        // lowest_price - 13% - ($3 if under $100) should be > bid_amount
        $profitAfterFees = $lowestPrice * 0.87; // Subtract 13% (eBay fees)
        
        // If card is under $100, subtract additional $3
        if ($lowestPrice < 100) {
            $profitAfterFees -= 3;
        }

        return $profitAfterFees > $bidAmount;
    }
}


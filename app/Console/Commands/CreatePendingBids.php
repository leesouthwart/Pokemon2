<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;
use App\Models\PendingBid;
use App\Services\EbayService;
use Carbon\Carbon;

class CreatePendingBids extends Command
{
    protected $signature = 'pending:create {--card= : Process only the card with this ID} {--grading=psa : Grading type to process (psa or cgc)}';

    protected $description = 'Find cards with PSA titles and create pending bids for matching eBay listings';

    public function handle()
    {
        $gradingType = strtolower((string) $this->option('grading'));

        if (!in_array($gradingType, ['psa', 'cgc'], true)) {
            $this->error('Invalid grading type. Use psa or cgc.');
            return Command::FAILURE;
        }

        $this->info('Starting pending bids creation for ' . strtoupper($gradingType) . '...');

        $user = \App\Models\User::where('email', 'leesouthwart@gmail.com')->first();

        if (!$user) {
            $this->error('User not found');
            return Command::FAILURE;
        }

        if ($user->balance < 100) {
            $this->warn("User balance ({$user->balance}) is below minimum threshold of $200. Skipping pending bid creation.");
            return Command::SUCCESS;
        }

        $cardIdFilter = $this->option('card');

        $cardsQuery = Card::whereNotNull('psa_title')
            ->where('excluded_from_sniping', false);

        if ($cardIdFilter !== null) {
            $cardsQuery->where('id', $cardIdFilter);
        }

        $cards = $cardsQuery->get();

        if ($cardIdFilter !== null && $cards->isEmpty()) {
            $this->error("Card with ID {$cardIdFilter} not found, has no PSA title, or is excluded from sniping");
            return Command::FAILURE;
        }

        $this->info("Found {$cards->count()} cards with PSA titles");

        $ebayService = new EbayService();
        $listings = $gradingType === 'cgc'
            ? $ebayService->getCgcJapaneseCgc10Auctions()
            : $ebayService->getPsaJapanesePsa10Auctions();

        $this->info('Found ' . count($listings) . ' eBay listings');

        $pendingBidsCreated = 0;
        $pendingBidsUpdated = 0;

        foreach ($listings as $listing) {
            $card = Card::findByPsaTitle($listing['title']);

            if (!$card) {
                continue;
            }

            if ($cardIdFilter !== null && (string) $card->id !== (string) $cardIdFilter) {
                continue;
            }

            $existingPendingBid = PendingBid::where('ebay_item_id', $listing['itemId'])
                ->where('grading_type', $gradingType)
                ->first();

            if ($existingPendingBid) {
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

            $bidAmount = $this->calculateBidAmount($card, $ebayService, $gradingType);

            if ($bidAmount <= 0) {
                $this->warn("Card {$card->id} has invalid bid price, skipping");
                continue;
            }

            if ($bidAmount <= ($listing['currentBid'] ?? 0)) {
                $this->warn("Bid amount {$bidAmount} is not higher than current bid " . ($listing['currentBid'] ?? 0) . " for item {$listing['itemId']}, skipping");
                continue;
            }

            PendingBid::create([
                'card_id' => $card->id,
                'grading_type' => $gradingType,
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

    private function calculateBidAmount(Card $card, EbayService $ebayService, string $gradingType): float
    {
        $fallbackBidAmount = (float) $card->getBidPrice();

        $searchTerms = [$card->search_term];
        $card->load('psaTitles');
        foreach ($card->psaTitles as $psaTitle) {
            $searchTerms[] = $psaTitle->title;
        }
        $searchTerms = array_values(array_unique(array_filter($searchTerms)));

        $byItemId = [];
        foreach ($searchTerms as $searchTerm) {
            $listings = $gradingType === 'cgc'
                ? $ebayService->searchCgc10BuyItNow($searchTerm)
                : $ebayService->searchPsa10BuyItNow($searchTerm);

            foreach ($listings as $row) {
                $itemId = $row['itemId'] ?? '';
                if ($itemId === '') {
                    continue;
                }
                $price = (float) ($row['price'] ?? 0);
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

        $gradeLabel = strtoupper($gradingType) . ' 10';

        if ($uniqueListingCount >= 3 && $lowestPrice !== null && $lowestPrice > 0) {
            $listingBasedBid = $this->calculateBidFromTargetProfit($lowestPrice, 0.15);
            if ($listingBasedBid > 0) {
                $this->info("Card {$card->id} using listing-based bid {$listingBasedBid} (lowest {$gradeLabel} listing: \${$lowestPrice}, unique listings: {$uniqueListingCount})");
                return $listingBasedBid;
            }
        }

        $this->info("Card {$card->id} using fallback buy+grade bid {$fallbackBidAmount} (unique {$gradeLabel} listings: {$uniqueListingCount})");
        return $fallbackBidAmount;
    }

    private function calculateBidFromTargetProfit(float $expectedSalePrice, float $targetProfitMargin): float
    {
        $netAfterFees = $this->calculateNetSaleAfterFees($expectedSalePrice);
        if ($netAfterFees <= 0) {
            return 0;
        }

        $maxBid = $netAfterFees / (1 + $targetProfitMargin);

        return (float) floor($maxBid);
    }

    private function calculateNetSaleAfterFees(float $salePrice): float
    {
        $net = $salePrice * 0.87;

        if ($salePrice < 100) {
            $net -= 3;
        }

        return $net;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;
use App\Models\PendingBid;
use App\Services\EbayService;
use App\Services\SnipingBidCalculator;
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

            $bidAmount = SnipingBidCalculator::calculate($card, $ebayService, $gradingType);

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
}

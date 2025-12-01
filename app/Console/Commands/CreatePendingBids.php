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

        if ($user->balance < 200) {
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
}


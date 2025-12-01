<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingBid;
use App\Models\User;
use App\Jobs\SubmitBidToGixen;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessPendingBids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bids:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending bids and submit to Gixen with rate limiting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pending bids processing...');

        // Get pending bids that:
        // 1. Haven't been submitted yet
        // 2. End within the next 3 hours
        // 3. End date is in the future (not expired)
        $threeHoursFromNow = now()->addHours(3);
        
        $pendingBids = PendingBid::where('bid_submitted', false)
            ->where(function($query) {
                $query->whereNull('status')
                      ->orWhere('status', '!=', 'cancelled due to low funds');
            })
            ->whereNotNull('end_date')
            ->where('end_date', '>', now())
            ->where('end_date', '<=', $threeHoursFromNow)
            ->orderBy('end_date', 'asc') // Process earliest ending first
            ->get();

        $this->info("Found {$pendingBids->count()} pending bids to process");

        if ($pendingBids->isEmpty()) {
            return Command::SUCCESS;
        }

        // Get the default user for bidding
        // TODO: You may want to modify this to use a specific system user or get user from pending bid
        $user = User::where('email', 'leesouthwart@gmail.com')->first();

        $jobsDispatched = 0;
        $delaySeconds = 0;

        foreach ($pendingBids as $pendingBid) {
            // Skip if already cancelled
            if ($pendingBid->status === 'cancelled due to low funds') {
                continue;
            }

            // Check if current bid is still below our bid amount
            // We'll do a quick check here, but the job will do a more thorough check
            $currentBid = $pendingBid->current_bid;
            
            if ($currentBid >= $pendingBid->bid_amount) {
                $this->warn("Skipping pending bid {$pendingBid->id}: current bid {$currentBid} >= bid amount {$pendingBid->bid_amount}");
                continue;
            }

            // Check user has minimum balance of $200
            if ($user->balance < 200) {
                $this->warn("User balance ({$user->balance}) is below minimum threshold. Cancelling pending bid {$pendingBid->id}");
                
                $pendingBid->update([
                    'status' => 'cancelled due to low funds',
                ]);
                
                continue;
            }

            // Check user has sufficient balance for this specific bid
            if ($user->balance < $pendingBid->bid_amount) {
                $this->warn("Skipping pending bid {$pendingBid->id}: insufficient balance ({$user->balance} < {$pendingBid->bid_amount})");
                continue;
            }

            // Dispatch job with delay to rate limit API calls
            // Stagger the jobs: first one immediately, then each subsequent one 10 seconds later
            // This ensures we don't spam Gixen's API
            SubmitBidToGixen::dispatch($pendingBid, $user)
                ->delay(now()->addSeconds($delaySeconds));

            $jobsDispatched++;
            $delaySeconds += 10; // 10 second gap between each bid submission

            $this->info("Dispatched bid job for pending bid {$pendingBid->id} (ebay item: {$pendingBid->ebay_item_id}) with {$delaySeconds}s delay");
        }

        $this->info("Dispatched {$jobsDispatched} bid jobs with rate limiting");

        return Command::SUCCESS;
    }
}


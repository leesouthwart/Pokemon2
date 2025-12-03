<?php

namespace App\Jobs;

use App\Models\PendingBid;
use App\Models\Bid;
use App\Models\User;
use App\Services\GixenService;
use App\Services\EbayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubmitBidToGixen implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pendingBid;
    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(PendingBid $pendingBid, User $user)
    {
        $this->pendingBid = $pendingBid;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Check if bid is still valid (not already submitted, not expired, price still below our bid)
            $this->pendingBid->refresh();
            
            if ($this->pendingBid->bid_submitted) {
                Log::info("Pending bid {$this->pendingBid->id} already submitted, skipping");
                return;
            }

            // Get current price from eBay to verify it's still below our bid amount
            $ebayService = new EbayService();
            $item = $ebayService->getItemById($this->pendingBid->ebay_item_id);
            
            if (!$item) {
                Log::warning("Could not fetch item {$itemId} from eBay");
                return;
            }

            // Get current bid price
            $currentBid = 0;
            if (isset($item['currentBidPrice']['value'])) {
                $currentBid = (float)$item['currentBidPrice']['value'];
            } elseif (isset($item['price']['value'])) {
                $currentBid = (float)$item['price']['value'];
            } elseif (isset($item['startingPrice']['value'])) {
                $currentBid = (float)$item['startingPrice']['value'];
            }

            // Only submit if current bid is still below our bid amount
            if ($currentBid >= $this->pendingBid->bid_amount) {
                Log::info("Current bid {$currentBid} is >= our bid amount {$this->pendingBid->bid_amount} for item {$this->pendingBid->ebay_item_id}, skipping");
                return;
            }

            // Refresh user balance to get latest
            $this->user->refresh();

            // Check user has minimum balance of $200
            if ($this->user->balance < 200) {
                // Check if we're mid-cycle (there are active bids)
                $activeBidsCount = Bid::where('user_id', $this->user->id)
                    ->where('status', 'submitted')
                    ->whereNotNull('end_date')
                    ->where('end_date', '>', now())
                    ->count();

                if ($activeBidsCount > 0) {
                    // Mid-cycle: mark as insufficient_funds for retry
                    Log::warning("User {$this->user->id} has balance below minimum threshold ({$this->user->balance} < 200) but has active bids. Marking pending bid {$this->pendingBid->id} as insufficient_funds for retry.");
                    
                    $this->pendingBid->update([
                        'status' => 'insufficient_funds',
                    ]);
                } else {
                    // Start of cycle: truly cancel
                    Log::warning("User {$this->user->id} has balance below minimum threshold ({$this->user->balance} < 200) and no active bids. Cancelling pending bid {$this->pendingBid->id}");
                    
                    $this->pendingBid->update([
                        'status' => 'cancelled due to low funds',
                    ]);
                }
                
                return;
            }

            // Check user has sufficient balance for this specific bid
            if ($this->user->balance < $this->pendingBid->bid_amount) {
                // Mark as insufficient_funds so it can be retried when balance increases
                Log::warning("User {$this->user->id} has insufficient balance ({$this->user->balance}) for bid amount {$this->pendingBid->bid_amount}. Marking as insufficient_funds for retry.");
                
                $this->pendingBid->update([
                    'status' => 'insufficient_funds',
                ]);
                
                return;
            }

            // Submit bid to Gixen (using extracted numeric item ID)
            $gixenService = new GixenService();
            $result = $gixenService->submitBid(
                $this->pendingBid->ebay_item_id,
                $this->pendingBid->bid_amount
            );

            if ($result['success']) {
                // Deduct balance from user
                $this->user->balance -= $this->pendingBid->bid_amount;
                $this->user->save();

                // Create Bid record
                $bid = Bid::create([
                    'user_id' => $this->user->id,
                    'pending_bid_id' => $this->pendingBid->id,
                    'card_id' => $this->pendingBid->card_id,
                    'ebay_item_id' => $this->pendingBid->ebay_item_id,
                    'ebay_title' => $this->pendingBid->ebay_title,
                    'bid_amount' => $this->pendingBid->bid_amount,
                    'currency' => $this->pendingBid->currency,
                    'end_date' => $this->pendingBid->end_date,
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);

                // Mark pending bid as submitted
                $this->pendingBid->update([
                    'bid_submitted' => true,
                    'bid_submitted_at' => now(),
                ]);

                // Dispatch job to check result 10 seconds after end date
                $checkDelay = Carbon::parse($this->pendingBid->end_date)->addSeconds(10);
                if ($checkDelay->isFuture()) {
                    CheckAuctionResult::dispatch($bid)->delay($checkDelay);
                } else {
                    // If end date has already passed, check immediately
                    CheckAuctionResult::dispatch($bid);
                }

                Log::info("Bid submitted successfully for item {$this->pendingBid->ebay_item_id}, bid ID: {$bid->id}");
            } else {
                Log::error("Failed to submit bid to Gixen for item {$this->pendingBid->ebay_item_id}: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error("Error submitting bid to Gixen: " . $e->getMessage(), [
                'pending_bid_id' => $this->pendingBid->id,
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Extract the numeric item ID from eBay's legacy format (e.g., "v1|306622440083|0" -> "306622440083")
     * 
     * @param string $itemId The item ID in any format
     * @return string The numeric item ID
     */
    private function extractItemId($itemId)
    {
        // If the item ID contains pipes, extract the middle part (e.g., "v1|306622440083|0")
        if (strpos($itemId, '|') !== false) {
            $parts = explode('|', $itemId);
            // Return the middle part (usually index 1)
            return $parts[1] ?? $itemId;
        }
        
        // If no pipes, return as-is (already numeric)
        return $itemId;
    }
}


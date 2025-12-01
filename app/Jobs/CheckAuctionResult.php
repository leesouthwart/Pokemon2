<?php

namespace App\Jobs;

use App\Models\Bid;
use App\Services\EbayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAuctionResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bid;

    /**
     * Create a new job instance.
     */
    public function __construct(Bid $bid)
    {
        $this->bid = $bid;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $this->bid->refresh();

            // Skip if already checked
            if ($this->bid->status !== 'submitted') {
                Log::info("Bid {$this->bid->id} status is {$this->bid->status}, skipping check");
                return;
            }

            // Get item from eBay
            $ebayService = new EbayService();
            $item = $ebayService->getItemById($this->bid->ebay_item_id);

            if (!$item) {
                Log::warning("Could not fetch item {$this->bid->ebay_item_id} from eBay for bid {$this->bid->id}");
                // Retry later - auction might have just ended and eBay needs a moment to update
                $this->release(60); // Release back to queue for 60 seconds
                return;
            }

            // Check if auction has ended (item might not be available if it just ended)
            // If the item is not found or doesn't have price info, retry
            if (!isset($item['currentBidPrice']) && !isset($item['price']) && !isset($item['startingPrice'])) {
                Log::info("Item {$this->bid->ebay_item_id} price info not available yet, retrying in 30 seconds");
                $this->release(30);
                return;
            }

            // Get end price
            $endPrice = 0;
            if (isset($item['currentBidPrice']['value'])) {
                $endPrice = (float)$item['currentBidPrice']['value'];
            } elseif (isset($item['price']['value'])) {
                $endPrice = (float)$item['price']['value'];
            } elseif (isset($item['startingPrice']['value'])) {
                $endPrice = (float)$item['startingPrice']['value'];
            }

            $this->bid->end_price = $endPrice;
            $this->bid->checked_at = now();

            // Check if we won
            // We won if the end price is less than or equal to our bid amount
            // (We might have won with a lower price if no one else bid higher)
            if ($endPrice > 0 && $endPrice <= $this->bid->bid_amount) {
                // We won - refund the full bid amount, then deduct only the end price
                $user = $this->bid->user;
                
                // Refund the full bid amount first
                $user->balance += $this->bid->bid_amount;
                
                // Then deduct only the actual winning price (end price)
                $user->balance -= $endPrice;
                $user->save();
                
                $this->bid->status = 'won_awaiting_confirmation';
                $this->bid->save();
                
                Log::info("Bid {$this->bid->id} won auction. End price: {$endPrice}, Bid amount: {$this->bid->bid_amount}. Refunded {$this->bid->bid_amount} and deducted {$endPrice} from user {$user->id}");
            } else {
                // We lost - refund the balance
                $this->bid->status = 'lost';
                $this->bid->save();

                // Refund balance to user
                $user = $this->bid->user;
                $user->balance += $this->bid->bid_amount;
                $user->save();

                // Update bid status to refunded
                $this->bid->status = 'refunded';
                $this->bid->save();

                Log::info("Bid {$this->bid->id} lost auction. End price: {$endPrice}, Bid amount: {$this->bid->bid_amount}. Refunded {$this->bid->bid_amount} to user {$user->id}");
            }
        } catch (\Exception $e) {
            Log::error("Error checking auction result: " . $e->getMessage(), [
                'bid_id' => $this->bid->id,
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }
}


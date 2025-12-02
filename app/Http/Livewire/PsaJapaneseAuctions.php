<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\EbayService;
use App\Services\GixenService;
use App\Models\PendingBid;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PsaJapaneseAuctions extends Component
{
    use WithPagination;

    public $allListings = [];
    public $bidAmounts = [];
    public $loading = true;
    public $error = null;
    public $bidStatus = []; // Track bid status per item
    public $showAllApiListings = false; // Toggle to show all API listings vs pending bids

    public function mount()
    {
        // Only allow access to user with email leesouthwart@gmail.com
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }
        
        $this->fetchListings();
    }

    public function updatedShowAllApiListings()
    {
        $this->fetchListings();
    }

    public function fetchListings()
    {
        $this->loading = true;
        $this->error = null;

        try {
            if ($this->showAllApiListings) {
                // Fetch all listings from eBay API
                $ebayService = new EbayService();
                $apiListings = $ebayService->getPsaJapanesePsa10Auctions();
                
                // Log if no listings returned (for debugging)
                if (empty($apiListings)) {
                    \Log::info('getPsaJapanesePsa10Auctions returned empty array', [
                        'environment' => app()->environment(),
                    ]);
                }
                
                // Get all pending bid item IDs for comparison
                $pendingBidItemIds = PendingBid::pluck('ebay_item_id')->toArray();
                
                // Convert API listings to format and add match status
                $this->allListings = collect($apiListings)->map(function ($listing) use ($pendingBidItemIds) {
                    $hasPendingBid = in_array($listing['itemId'], $pendingBidItemIds);
                    $matchingCard = \App\Models\Card::findByPsaTitle($listing['title']);
                    
                    return [
                        'itemId' => $listing['itemId'],
                        'title' => $listing['title'],
                        'image' => $listing['image'] ?? '',
                        'currentBid' => (float)($listing['currentBid'] ?? 0),
                        'currency' => $listing['currency'] ?? 'USD',
                        'url' => $listing['url'] ?? '',
                        'endDate' => $listing['endDate'] ?? null,
                        'bidAmount' => null,
                        'pendingBidId' => null,
                        'hasPendingBid' => $hasPendingBid,
                        'hasMatchingCard' => $matchingCard !== null,
                        'matchingCardId' => $matchingCard ? $matchingCard->id : null,
                    ];
                })->toArray();
            } else {
                // Get pending bids that haven't been submitted yet and aren't cancelled
                $pendingBids = PendingBid::where('bid_submitted', false)
                    ->where(function($query) {
                        $query->whereNull('status')
                              ->orWhere('status', '!=', 'cancelled due to low funds');
                    })
                    ->whereNotNull('end_date')
                    ->where('end_date', '>', now())
                    ->with('card')
                    ->get();

                // Convert pending bids to listing format
                $this->allListings = $pendingBids->map(function ($pendingBid) {
                    return [
                        'itemId' => $pendingBid->ebay_item_id,
                        'title' => $pendingBid->ebay_title,
                        'image' => $pendingBid->ebay_image_url ?? '',
                        'currentBid' => (float)$pendingBid->current_bid,
                        'currency' => $pendingBid->currency,
                        'url' => $pendingBid->ebay_url,
                        'endDate' => $pendingBid->end_date ? $pendingBid->end_date->toIso8601String() : null,
                        'bidAmount' => (float)$pendingBid->bid_amount,
                        'pendingBidId' => $pendingBid->id,
                        'hasPendingBid' => true,
                        'hasMatchingCard' => true,
                    ];
                })->toArray();
            }

            $this->resetPage(); // Reset to first page when fetching new data
        } catch (\Exception $e) {
            $this->error = 'Failed to fetch listings: ' . $e->getMessage();
            $this->allListings = [];
        } finally {
            $this->loading = false;
        }
    }

    public function submitBid($itemId)
    {
        if (!Auth::check()) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'You must be logged in to place bids.'
            ];
            return;
        }

        $bidAmount = $this->bidAmounts[$itemId] ?? null;
        
        if (!$bidAmount || $bidAmount <= 0) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Please enter a valid bid amount.'
            ];
            return;
        }

        // Find the listing to get current bid info
        $listing = collect($this->allListings)->firstWhere('itemId', $itemId);
        
        if (!$listing) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Listing not found.'
            ];
            return;
        }

        // Validate bid amount is higher than current bid
        $currentBid = $listing['currentBid'] ?? 0;
        if ($bidAmount <= $currentBid) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Bid amount must be higher than current bid of $' . number_format($currentBid, 2) . '.'
            ];
            return;
        }

        try {
            $gixenService = new GixenService();
            $result = $gixenService->submitBid($itemId, $bidAmount);

            if ($result['success']) {
                // Mark pending bid as submitted
                $listing = collect($this->allListings)->firstWhere('itemId', $itemId);
                if (isset($listing['pendingBidId'])) {
                    $pendingBid = PendingBid::find($listing['pendingBidId']);
                    if ($pendingBid) {
                        $pendingBid->update([
                            'bid_submitted' => true,
                            'bid_submitted_at' => now(),
                        ]);
                    }
                }

                $this->bidStatus[$itemId] = [
                    'success' => true,
                    'message' => $result['message']
                ];
            } else {
                $this->bidStatus[$itemId] = [
                    'success' => false,
                    'message' => $result['message']
                ];
            }
        } catch (\Exception $e) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Error placing bid: ' . $e->getMessage()
            ];
        }
    }

    public function render()
    {
        // Sort listings by price (lowest first), then paginate - 20 per page
        $perPage = 20;
        $currentPage = $this->getPage();
        $items = collect($this->allListings)
            ->sortBy('currentBid')
            ->values();
        $currentItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        foreach ($currentItems as $listing) {
            if (!isset($this->bidAmounts[$listing['itemId']])) {
                // Use the calculated bid amount from pending bid if available, otherwise default to currentBid + 0.5
                $this->bidAmounts[$listing['itemId']] = $listing['bidAmount'] ?? round($listing['currentBid'] + 0.5, 2);
            }
        }
        
        $listings = new LengthAwarePaginator(
            $currentItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.psa-japanese-auctions', [
            'listings' => $listings,
        ]);
    }
}


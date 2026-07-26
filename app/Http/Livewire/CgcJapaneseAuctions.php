<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\GixenService;
use App\Services\SnipingBidCalculator;
use App\Models\PendingBid;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CgcJapaneseAuctions extends Component
{
    use WithPagination;

    public $allListings = [];
    public $bidAmounts = [];
    public $loading = true;
    public $error = null;
    public $bidStatus = [];

    public function mount()
    {
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $this->fetchListings();
    }

    public function fetchListings()
    {
        $this->loading = true;
        $this->error = null;

        try {
            $pendingBids = PendingBid::where('bid_submitted', false)
                ->where('grading_type', 'cgc')
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', 'cancelled due to low funds');
                })
                ->whereNotNull('end_date')
                ->where('end_date', '>', now())
                ->with('card')
                ->get();

            $this->allListings = $pendingBids->map(function ($pendingBid) {
                return [
                    'itemId' => $pendingBid->ebay_item_id,
                    'title' => $pendingBid->ebay_title,
                    'image' => $pendingBid->ebay_image_url ?? '',
                    'currentBid' => (float) $pendingBid->current_bid,
                    'currency' => $pendingBid->currency,
                    'url' => $pendingBid->ebay_url,
                    'endDate' => $pendingBid->end_date ? $pendingBid->end_date->toIso8601String() : null,
                    'bidAmount' => (float) $pendingBid->bid_amount,
                    'pendingBidId' => $pendingBid->id,
                    'hasPendingBid' => true,
                    'hasMatchingCard' => true,
                ];
            })->toArray();

            $this->resetPage();
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
                'message' => 'You must be logged in to place bids.',
            ];
            return;
        }

        if (Auth::user()->email !== 'leesouthwart@gmail.com') {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Unauthorized: Only authorized users can place bids.',
            ];
            return;
        }

        $bidAmount = $this->bidAmounts[$itemId] ?? null;

        if (!$bidAmount || $bidAmount <= 0) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Please enter a valid bid amount.',
            ];
            return;
        }

        $listing = collect($this->allListings)->firstWhere('itemId', $itemId);

        if (!$listing) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Listing not found.',
            ];
            return;
        }

        $currentBid = $listing['currentBid'] ?? 0;
        if ($bidAmount <= $currentBid) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Bid amount must be higher than current bid of $' . number_format($currentBid, 2) . '.',
            ];
            return;
        }

        try {
            $gixenService = new GixenService();
            $result = $gixenService->submitBid($itemId, $bidAmount);

            if ($result['success']) {
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
                    'message' => $result['message'],
                ];
            } else {
                $this->bidStatus[$itemId] = [
                    'success' => false,
                    'message' => $result['message'],
                ];
            }
        } catch (\Exception $e) {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Error placing bid: ' . $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        $perPage = 20;
        $currentPage = $this->getPage();
        $items = collect($this->allListings)
            ->sortBy('currentBid')
            ->values();
        $currentItems = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        foreach ($currentItems as $listing) {
            if (!isset($this->bidAmounts[$listing['itemId']])) {
                $this->bidAmounts[$listing['itemId']] = SnipingBidCalculator::defaultBidInput(
                    $listing['bidAmount'] ?? null,
                    $listing['currentBid']
                );
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

        return view('livewire.cgc-japanese-auctions', [
            'listings' => $listings,
        ]);
    }
}

<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\EbayService;
use App\Services\GixenService;
use App\Services\SnipingBidCalculator;
use App\Models\PendingBid;
use App\Models\Card;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CgcJapaneseApiListings extends Component
{
    use WithPagination;

    public $allListings = [];
    public $bidAmounts = [];
    public $loading = true;
    public $error = null;
    public $bidStatus = [];

    public $cardFilter = 'both';
    public $pendingBidFilter = 'both';

    public function mount()
    {
        if (!Auth::check() || Auth::user()->email !== 'leesouthwart@gmail.com') {
            abort(403, 'Unauthorized access');
        }

        $this->fetchListings();
    }

    public function updatedCardFilter()
    {
        $this->resetPage();
    }

    public function updatedPendingBidFilter()
    {
        $this->resetPage();
    }

    public function fetchListings()
    {
        $this->loading = true;
        $this->error = null;

        try {
            $ebayService = new EbayService();
            $apiListings = $ebayService->getCgcJapaneseCgc10Auctions();

            $pendingBids = PendingBid::where('grading_type', 'cgc')
                ->where('bid_submitted', false)
                ->get()
                ->keyBy('ebay_item_id');

            $this->allListings = collect($apiListings)->map(function ($listing) use ($pendingBids, $ebayService) {
                $matchingCard = Card::findByPsaTitle($listing['title']);
                $pendingBid = $pendingBids->get($listing['itemId']) ?? null;

                if ($pendingBid) {
                    $pendingBid->update([
                        'ebay_title' => $listing['title'],
                        'ebay_image_url' => $listing['image'] ?? null,
                        'ebay_url' => $listing['url'] ?? '',
                        'current_bid' => $listing['currentBid'] ?? 0,
                        'end_date' => isset($listing['endDate']) ? Carbon::parse($listing['endDate']) : null,
                    ]);
                } elseif ($matchingCard) {
                    $bidAmount = SnipingBidCalculator::calculate($matchingCard, $ebayService, 'cgc');

                    if ($bidAmount > 0 && $bidAmount > ($listing['currentBid'] ?? 0)) {
                        $pendingBid = PendingBid::create([
                            'card_id' => $matchingCard->id,
                            'grading_type' => 'cgc',
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
                    }
                }

                return [
                    'itemId' => $listing['itemId'],
                    'title' => $listing['title'],
                    'image' => $listing['image'] ?? '',
                    'currentBid' => (float) ($listing['currentBid'] ?? 0),
                    'currency' => $listing['currency'] ?? 'USD',
                    'url' => $listing['url'] ?? '',
                    'endDate' => $listing['endDate'] ?? null,
                    'bidAmount' => $pendingBid ? (float) $pendingBid->bid_amount : null,
                    'pendingBidId' => $pendingBid?->id,
                    'hasPendingBid' => $pendingBid !== null,
                    'hasMatchingCard' => $matchingCard !== null,
                    'matchingCardId' => $matchingCard ? $matchingCard->id : null,
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
                if (!empty($listing['pendingBidId'])) {
                    PendingBid::find($listing['pendingBidId'])?->update([
                        'bid_amount' => $bidAmount,
                        'bid_submitted' => true,
                        'bid_submitted_at' => now(),
                    ]);
                } else {
                    PendingBid::create([
                        'card_id' => $listing['matchingCardId'] ?? null,
                        'grading_type' => 'cgc',
                        'ebay_item_id' => $listing['itemId'],
                        'ebay_title' => $listing['title'],
                        'ebay_image_url' => $listing['image'] ?? null,
                        'ebay_url' => $listing['url'] ?? '',
                        'current_bid' => $listing['currentBid'] ?? 0,
                        'bid_amount' => $bidAmount,
                        'currency' => $listing['currency'] ?? 'USD',
                        'end_date' => !empty($listing['endDate']) ? Carbon::parse($listing['endDate']) : null,
                        'bid_submitted' => true,
                        'bid_submitted_at' => now(),
                    ]);
                }

                $this->fetchListings();

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

    protected function getFilteredListings()
    {
        return collect($this->allListings)->filter(function ($listing) {
            if ($this->cardFilter === 'only_no_card' && $listing['hasMatchingCard']) {
                return false;
            }
            if ($this->cardFilter === 'card_only' && !$listing['hasMatchingCard']) {
                return false;
            }

            if ($this->pendingBidFilter === 'with_pending_bid' && !$listing['hasPendingBid']) {
                return false;
            }
            if ($this->pendingBidFilter === 'without_pending_bid' && $listing['hasPendingBid']) {
                return false;
            }

            return true;
        });
    }

    public function render()
    {
        $perPage = 20;
        $currentPage = $this->getPage();
        $items = $this->getFilteredListings()
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

        return view('livewire.cgc-japanese-api-listings', [
            'listings' => $listings,
        ]);
    }
}

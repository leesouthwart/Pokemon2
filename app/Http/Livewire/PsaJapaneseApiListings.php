<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\EbayService;
use App\Services\GixenService;
use App\Models\PendingBid;
use App\Models\Card;
use App\Models\PsaTitle;
use App\Jobs\CreateCard;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class PsaJapaneseApiListings extends Component
{
    use WithPagination;

    public $allListings = [];
    public $bidAmounts = [];
    public $loading = true;
    public $error = null;
    public $bidStatus = []; // Track bid status per item
    
    // Filter properties
    public $cardFilter = 'both'; // 'only_no_card', 'card_only', 'both'
    public $pendingBidFilter = 'both'; // 'with_pending_bid', 'without_pending_bid', 'both'
    
    // Modal properties
    public $showModal = false;
    public $selectedListing = null;
    public $activeTab = 'create'; // 'create' or 'search'
    public $modalMessage = null;
    public $modalMessageType = null; // 'success' or 'error'
    public $creatingCard = false;
    
    // Tab 1: Create card form
    public $newCardSearchTerm = '';
    public $newCardUrl = '';
    
    // Tab 2: Search existing cards
    public $cardSearchQuery = '';
    public $searchResults = [];
    public $selectedCardId = null;
    public $selectedCardPsaTitles = []; // Store existing PSA titles for selected card

    public function mount()
    {
        // Only allow access to user with email leesouthwart@gmail.com
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

    public function openModal($itemId)
    {
        $listing = collect($this->allListings)->firstWhere('itemId', $itemId);
        
        if ($listing && !$listing['hasMatchingCard']) {
            $this->selectedListing = $listing;
            $this->showModal = true;
            $this->activeTab = 'create';
            $this->newCardSearchTerm = '';
            $this->newCardUrl = '';
            $this->cardSearchQuery = '';
            $this->searchResults = [];
            $this->selectedCardId = null;
            $this->selectedCardPsaTitles = [];
            $this->modalMessage = null;
            $this->modalMessageType = null;
            $this->creatingCard = false;
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedListing = null;
        $this->activeTab = 'create';
        $this->newCardSearchTerm = '';
        $this->newCardUrl = '';
        $this->cardSearchQuery = '';
        $this->searchResults = [];
        $this->selectedCardId = null;
        $this->selectedCardPsaTitles = [];
        $this->modalMessage = null;
        $this->modalMessageType = null;
        $this->creatingCard = false;
    }

    public function updatedCardSearchQuery()
    {
        if (strlen($this->cardSearchQuery) >= 2) {
            $this->searchCards();
        } else {
            $this->searchResults = [];
        }
    }

    public function searchCards()
    {
        $query = $this->cardSearchQuery;
        
        if (strlen($query) < 2) {
            $this->searchResults = [];
            return;
        }
        
        $this->searchResults = Card::where(function($q) use ($query) {
                $q->where('search_term', 'like', "%{$query}%")
                  ->orWhere('psa_title', 'like', "%{$query}%")
                  ->orWhereHas('psaTitles', function($q2) use ($query) {
                      $q2->where('title', 'like', "%{$query}%");
                  });
            })
            ->with('psaTitles')
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get()
            ->map(function($card) {
                return [
                    'id' => $card->id,
                    'search_term' => $card->search_term,
                    'image_url' => $card->image_url,
                    'psa_title' => $card->psa_title, // Keep for backward compatibility
                    'psa_titles' => $card->psaTitles->pluck('title')->toArray(),
                ];
            })
            ->toArray();
    }

    public function selectCard($cardId)
    {
        $this->selectedCardId = $cardId;
        $card = Card::with('psaTitles')->find($cardId);
        if ($card) {
            $this->selectedCardPsaTitles = $card->psaTitles->pluck('title')->toArray();
        } else {
            $this->selectedCardPsaTitles = [];
        }
    }

    public function createNewCard()
    {
        $this->validate([
            'newCardSearchTerm' => 'required|string|max:255',
            'newCardUrl' => 'required|url',
        ]);

        if (!$this->selectedListing) {
            $this->modalMessage = 'No listing selected.';
            $this->modalMessageType = 'error';
            return;
        }

        $this->creatingCard = true;

        try {
            // Dispatch CreateCard job with psa_title
            CreateCard::dispatch(
                $this->newCardSearchTerm,
                $this->newCardUrl,
                '', // groups
                $this->selectedListing['title'] // psa_title
            );

            $this->modalMessage = 'Card creation job has been dispatched. The card will be linked once the job completes.';
            $this->modalMessageType = 'success';
            $this->creatingCard = false;
            
            // Refresh listings
            $this->fetchListings();
            
            // Close modal after showing success message
            $this->dispatch('card-created');
            $this->closeModal();
        } catch (\Exception $e) {
            $this->modalMessage = 'Error creating card: ' . $e->getMessage();
            $this->modalMessageType = 'error';
            $this->creatingCard = false;
        }
    }

    public function linkExistingCard()
    {
        if (!$this->selectedCardId || !$this->selectedListing) {
            $this->modalMessage = 'Please select a card to link.';
            $this->modalMessageType = 'error';
            return;
        }

        $card = Card::find($this->selectedCardId);
        
        if ($card) {
            try {
                // Check if this PSA title already exists for this card
                $existingTitle = $card->psaTitles()->where('title', $this->selectedListing['title'])->first();
                
                if (!$existingTitle) {
                    // Add new PSA title
                    $card->psaTitles()->create([
                        'title' => $this->selectedListing['title'],
                    ]);
                    
                    $this->modalMessage = 'PSA title successfully added to card!';
                } else {
                    $this->modalMessage = 'This PSA title already exists for this card.';
                }
                
                $this->modalMessageType = 'success';
                
                // Refresh listings
                $this->fetchListings();
                
                // Close modal and dispatch event
                $this->dispatch('card-linked');
                $this->closeModal();
            } catch (\Exception $e) {
                $this->modalMessage = 'Error linking card: ' . $e->getMessage();
                $this->modalMessageType = 'error';
            }
        } else {
            $this->modalMessage = 'Card not found.';
            $this->modalMessageType = 'error';
        }
    }

    public function fetchListings()
    {
        $this->loading = true;
        $this->error = null;

        try {
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

        // Only allow user with email 'leesouthwart@gmail.com' to set pending bids
        if (Auth::user()->email !== 'leesouthwart@gmail.com') {
            $this->bidStatus[$itemId] = [
                'success' => false,
                'message' => 'Unauthorized: Only authorized users can place bids.'
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

    protected function getFilteredListings()
    {
        return collect($this->allListings)->filter(function ($listing) {
            // Apply card filter
            if ($this->cardFilter === 'only_no_card' && $listing['hasMatchingCard']) {
                return false;
            }
            if ($this->cardFilter === 'card_only' && !$listing['hasMatchingCard']) {
                return false;
            }
            
            // Apply pending bid filter
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
        // Apply filters and sort listings by price (lowest first), then paginate - 20 per page
        $perPage = 20;
        $currentPage = $this->getPage();
        $items = $this->getFilteredListings()
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

        return view('livewire.psa-japanese-api-listings', [
            'listings' => $listings,
        ]);
    }
}

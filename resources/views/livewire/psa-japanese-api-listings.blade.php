<div class="bg-gray-900 py-10">
    <div class="px-4 mb-6 flex flex-wrap items-center gap-4">
        <h2 class="text-2xl font-bold text-white">PSA Japanese PSA 10 API Listings</h2>
        <div class="ml-auto flex items-center gap-3">
            <a 
                href="{{ route('psa-japanese-auctions') }}"
                class="bg-blue-700 hover:bg-blue-800 text-white font-bold px-4 py-2 rounded transition"
            >
                Back to Pending Bids
            </a>
            <a 
                href="{{ route('cards.psa-title.index') }}"
                class="bg-green-700 hover:bg-green-800 text-white font-bold px-4 py-2 rounded transition"
            >
                Manage PSA Titles
            </a>
            <button 
                wire:click="fetchListings" 
                class="bg-indigo-700 hover:bg-indigo-800 text-white font-bold px-4 py-2 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                @if($loading) disabled @endif
            >
                @if($loading)
                    <span>Loading...</span>
                @else
                    <span>Refresh</span>
                @endif
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="px-4 mb-6 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
                <span class="text-sm text-white">Card Filter:</span>
                <select 
                    wire:model.live="cardFilter"
                    class="rounded bg-gray-800 border border-gray-700 text-gray-200 text-sm px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                >
                    <option value="both">Both</option>
                    <option value="card_only">Card Only</option>
                    <option value="only_no_card">Only No Card</option>
                </select>
            </label>
            
            <label class="flex items-center gap-2">
                <span class="text-sm text-white">Pending Bid Filter:</span>
                <select 
                    wire:model.live="pendingBidFilter"
                    class="rounded bg-gray-800 border border-gray-700 text-gray-200 text-sm px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                >
                    <option value="both">Both</option>
                    <option value="with_pending_bid">With Pending Bid</option>
                    <option value="without_pending_bid">Without Pending Bid</option>
                </select>
            </label>
        </div>
    </div>

    @if($error)
        <div class="px-4 mb-4">
            <div class="bg-red-500 text-white p-4 rounded">
                {{ $error }}
            </div>
        </div>
    @endif

    @if($loading && empty($listings))
        <div class="px-4">
            <div class="text-white">Loading listings...</div>
        </div>
    @elseif(empty($listings))
        <div class="px-4">
            <div class="text-white">No listings found.</div>
        </div>
    @else
        <table class="mt-6 w-full whitespace-nowrap text-left">
            <colgroup>
                <col class="w-1/12">
                <col class="w-5/12">
                <col class="w-2/12">
                <col class="w-2/12">
                <col class="w-2/12">
                <col class="w-2/12">
            </colgroup>
            <thead class="border-b border-white/10 text-sm leading-6 text-white">
                <tr>
                    <th scope="col" class="py-2 px-2 font-semibold">Image</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Title</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Current Bid</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Ends</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Status</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Bid</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($listings as $listing)
                    <tr class="hover:bg-gray-800 {{ !($listing['hasMatchingCard'] ?? false) ? 'cursor-pointer' : '' }}" 
                        @if(!($listing['hasMatchingCard'] ?? false))
                            wire:click="openModal('{{ $listing['itemId'] }}')"
                        @endif>
                        <td class="py-2 px-2">
                            <div class="flex items-center">
                                <img src="{{ $listing['image'] }}" alt="{{ $listing['title'] }}" class="aspect-[4/5] h-24 object-contain">
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <div class="flex items-center">
                                <a href="{{ $listing['url'] }}" target="_blank" class="text-sm font-medium leading-5 text-white hover:text-indigo-400">
                                    {{ $listing['title'] }}
                                </a>
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <div class="font-mono text-sm leading-5 text-gray-400">
                               ${{ number_format($listing['currentBid'], 2) }}
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <div class="text-sm leading-5 text-gray-400">
                                @if($listing['endDate'])
                                    {{ \Carbon\Carbon::parse($listing['endDate'])->diffForHumans() }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <div class="flex flex-col gap-1 text-xs">
                                @if($listing['hasPendingBid'] ?? false)
                                    <span class="px-2 py-1 bg-green-600 text-white rounded">Has Pending Bid</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-600 text-white rounded">No Pending Bid</span>
                                @endif
                                @if($listing['hasMatchingCard'] ?? false)
                                    <span class="px-2 py-1 bg-blue-600 text-white rounded">Has Matching Card (ID: {{ $listing['matchingCardId'] ?? 'N/A' }})</span>
                                @else
                                    <span class="px-2 py-1 bg-red-600 text-white rounded">No Matching Card</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-2 px-2">
                            <div class="flex flex-col gap-2" wire:click.stop>
                                <form wire:submit.prevent="submitBid('{{ $listing['itemId'] }}')" class="flex items-center gap-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="{{ $listing['currentBid'] + 0.01 }}"
                                        wire:model.defer="bidAmounts.{{ $listing['itemId'] }}"
                                        class="w-24 rounded bg-gray-800 border border-gray-700 text-gray-200 text-sm px-2 py-1 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                                        placeholder="Amount"
                                        value="{{ $bidAmounts[$listing['itemId']] ?? '' }}"
                                    >
                                    <button
                                        type="submit"
                                        class="bg-indigo-700 hover:bg-indigo-800 text-white text-sm font-semibold px-3 py-1 rounded ml-2"
                                    >
                                        Bid
                                    </button>
                                </form>
                                @if(isset($bidStatus[$listing['itemId']]))
                                    <div class="text-xs {{ $bidStatus[$listing['itemId']]['success'] ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $bidStatus[$listing['itemId']]['message'] }}
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6 px-4">
            @if($listings->total() > 20)
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing {{ $listings->firstItem() }} to {{ $listings->lastItem() }} of {{ $listings->total() }} results
                    </div>
                    <div>
                        {{ $listings->links() }}
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Modal -->
    @if($showModal && $selectedListing)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click="closeModal">
            <div class="bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-white">Link Card to Listing</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="mb-4 p-3 bg-gray-700 rounded">
                        <p class="text-sm text-gray-300 mb-1"><strong>Listing Title:</strong></p>
                        <p class="text-white">{{ $selectedListing['title'] }}</p>
                    </div>

                    @if($modalMessage)
                        <div class="mb-4 p-3 rounded {{ $modalMessageType === 'success' ? 'bg-green-600' : 'bg-red-600' }} text-white">
                            {{ $modalMessage }}
                        </div>
                    @endif

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-700 mb-4">
                        <button 
                            wire:click="$set('activeTab', 'create')"
                            class="px-4 py-2 text-sm font-medium {{ $activeTab === 'create' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-gray-400 hover:text-white' }}"
                        >
                            Create New Card
                        </button>
                        <button 
                            wire:click="$set('activeTab', 'search')"
                            class="px-4 py-2 text-sm font-medium {{ $activeTab === 'search' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-gray-400 hover:text-white' }}"
                        >
                            Search Existing Cards
                        </button>
                    </div>

                    <!-- Tab 1: Create New Card -->
                    @if($activeTab === 'create')
                        <form wire:submit.prevent="createNewCard" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Search Term
                                </label>
                                <input
                                    type="text"
                                    wire:model="newCardSearchTerm"
                                    class="w-full rounded bg-gray-700 border border-gray-600 text-white px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                                    placeholder="Enter card search term"
                                    required
                                >
                                @error('newCardSearchTerm')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    URL
                                </label>
                                <input
                                    type="url"
                                    wire:model="newCardUrl"
                                    class="w-full rounded bg-gray-700 border border-gray-600 text-white px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                                    placeholder="Enter card URL"
                                    required
                                >
                                @error('newCardUrl')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    wire:click="closeModal"
                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if($creatingCard) disabled @endif
                                >
                                    @if($creatingCard)
                                        Creating...
                                    @else
                                        Create Card
                                    @endif
                                </button>
                            </div>
                        </form>
                    @endif

                    <!-- Tab 2: Search Existing Cards -->
                    @if($activeTab === 'search')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">
                                    Search Cards
                                </label>
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="cardSearchQuery"
                                    class="w-full rounded bg-gray-700 border border-gray-600 text-white px-3 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20"
                                    placeholder="Type to search by search term or PSA title..."
                                >
                            </div>

                            @if(strlen($cardSearchQuery) >= 2)
                                @if(count($searchResults) > 0)
                                    <div class="max-h-96 overflow-y-auto space-y-2">
                                        @foreach($searchResults as $result)
                                            <div 
                                                wire:click="selectCard({{ $result['id'] }})"
                                                class="p-3 rounded border-2 cursor-pointer transition {{ $selectedCardId == $result['id'] ? 'border-indigo-500 bg-indigo-900/20' : 'border-gray-600 bg-gray-700 hover:border-gray-500' }}"
                                            >
                                                <div class="flex items-center gap-3">
                                                    @if($result['image_url'])
                                                        <img src="{{ $result['image_url'] }}" alt="{{ $result['search_term'] }}" class="h-16 w-16 object-contain">
                                                    @else
                                                        <div class="h-16 w-16 bg-gray-600 flex items-center justify-center text-xs text-gray-400">No Image</div>
                                                    @endif
                                                    <div class="flex-1">
                                                        <p class="text-white font-medium">{{ $result['search_term'] }}</p>
                                                        @if($result['psa_title'])
                                                            <p class="text-sm text-gray-400">PSA Title: {{ $result['psa_title'] }}</p>
                                                        @endif
                                                    </div>
                                                    @if($selectedCardId == $result['id'])
                                                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-400">
                                        No cards found matching "{{ $cardSearchQuery }}"
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-8 text-gray-400">
                                    Type at least 2 characters to search
                                </div>
                            @endif

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700">
                                <button
                                    wire:click="closeModal"
                                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded"
                                >
                                    Cancel
                                </button>
                                <button
                                    wire:click="linkExistingCard"
                                    @if(!$selectedCardId) disabled @endif
                                    class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Confirm Link
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

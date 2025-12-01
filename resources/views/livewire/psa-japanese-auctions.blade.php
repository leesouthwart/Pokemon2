<div class="bg-gray-900 py-10">
    <div class="px-4 mb-6 flex flex-wrap items-center gap-4">
        <h2 class="text-2xl font-bold text-white">PSA Japanese PSA 10 Auctions (Ending in 24 Hours)</h2>
        <div class="ml-auto flex items-center gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <span class="text-sm text-white">Show All API Listings</span>
                <input
                    type="checkbox"
                    wire:model.live="showAllApiListings"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </label>
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
            </colgroup>
            <thead class="border-b border-white/10 text-sm leading-6 text-white">
                <tr>
                    <th scope="col" class="py-2 px-2 font-semibold">Image</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Title</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Current Bid</th>
                    <th scope="col" class="py-2 px-2 font-semibold">Ends</th>
                    @if($showAllApiListings)
                        <th scope="col" class="py-2 px-2 font-semibold">Status</th>
                    @endif
                    <th scope="col" class="py-2 px-2 font-semibold">Bid</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach($listings as $listing)
                    <tr class="hover:bg-gray-800">
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
                        @if($showAllApiListings)
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
                        @endif
                        <td class="py-2 px-2">
                            <div class="flex flex-col gap-2">
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
</div>


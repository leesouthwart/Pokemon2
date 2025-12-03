<x-app-layout>
    <div class="bg-gray-900 py-10">
        <div class="px-4 mb-6">
            <h2 class="text-2xl font-bold text-white mb-4">Won Bids Awaiting Confirmation</h2>
            
            @if(session('success'))
                <div class="bg-green-500 text-white p-4 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-500 text-white p-4 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($bids->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Card / Title
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Bid Amount
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    End Price
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    End Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-800 divide-y divide-gray-700">
                            @foreach($bids as $bid)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($bid->card && $bid->card->image_url)
                                                <img src="{{ $bid->card->image_url }}" alt="Card image" class="h-16 w-16 object-cover rounded mr-4">
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-white">
                                                    {{ $bid->ebay_title }}
                                                </div>
                                                @if($bid->card)
                                                    <div class="text-sm text-gray-400">
                                                        Card ID: {{ $bid->card->id }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                        ${{ number_format($bid->bid_amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                        ${{ number_format($bid->end_price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                        {{ $bid->end_date ? $bid->end_date->format('Y-m-d H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex gap-2">
                                            <form action="{{ route('bids.won.confirm', $bid->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold px-4 py-2 rounded transition">
                                                    Confirm
                                                </button>
                                            </form>
                                            <form action="{{ route('bids.won.decline', $bid->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="bg-red-700 hover:bg-red-800 text-white font-bold px-4 py-2 rounded transition">
                                                    Decline
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $bids->links() }}
                </div>
            @else
                <div class="bg-gray-800 p-6 rounded text-center">
                    <p class="text-gray-400">No won bids awaiting confirmation.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>


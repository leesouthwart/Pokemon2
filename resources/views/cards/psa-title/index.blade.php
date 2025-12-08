<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">PSA Title Management</h2>
                        <a 
                            href="{{ route('psa-japanese-api-listings') }}"
                            class="bg-blue-700 hover:bg-blue-800 text-white font-bold px-4 py-2 rounded transition"
                        >
                            Back to API Listings
                        </a>
                    </div>

                    <!-- Filter Toggles -->
                    <div class="mb-6 flex gap-4 items-center">
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                id="hide_with_title"
                                checked
                                onchange="updateFilters()"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Hide cards with PSA title
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                id="hide_excluded"
                                checked
                                onchange="updateFilters()"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Hide excluded cards
                            </span>
                        </label>
                    </div>

                    <!-- Search Form -->
                    <form method="GET" action="{{ route('cards.psa-title.search') }}" id="searchForm" class="mb-6">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                name="q"
                                value="{{ $query ?? '' }}"
                                placeholder="Search by PSA title or search term..."
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                onkeyup="if(event.key === 'Enter') this.form.submit()"
                            >
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Search
                            </button>
                            @if(isset($query))
                                @php
                                    $clearUrl = route('cards.psa-title.index');
                                    $hideWithTitle = request('hide_with_title');
                                    $hideExcluded = request('hide_excluded');
                                    if ($hideWithTitle !== null || $hideExcluded !== null) {
                                        $params = [];
                                        if ($hideWithTitle !== null) {
                                            $params['hide_with_title'] = $hideWithTitle;
                                        }
                                        if ($hideExcluded !== null) {
                                            $params['hide_excluded'] = $hideExcluded;
                                        }
                                        $clearUrl .= '?' . http_build_query($params);
                                    }
                                @endphp
                                <a
                                    href="{{ $clearUrl }}"
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                >
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($cards->count() > 0)
                        <!-- Bulk Actions -->
                        <div class="mb-4 flex items-center gap-4">
                            <form method="POST" action="{{ route('cards.psa-title.bulk-exclude') }}" id="bulk-exclude-form" class="flex items-center gap-2">
                                @csrf
                                @php
                                    $hideWithTitle = request('hide_with_title', '1');
                                    $hideExcluded = request('hide_excluded', '1');
                                @endphp
                                <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                                <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">
                                <div id="selected-card-ids-container"></div>
                                <button
                                    type="submit"
                                    id="bulk-exclude-btn"
                                    disabled
                                    class="bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white font-semibold px-4 py-2 rounded text-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Exclude Selected (<span id="selected-count">0</span>)
                                </button>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            <input
                                                type="checkbox"
                                                id="select-all"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                onchange="toggleSelectAll(this.checked)"
                                            >
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Image
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Search Term
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            PSA Title
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Excluded
                                        </th>
                                        <th class="px-6 py-3 border-b border-gray-200 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800">
                                    @foreach($cards as $card)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $card->excluded_from_sniping ? 'opacity-50' : '' }}">
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                <input
                                                    type="checkbox"
                                                    class="card-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    value="{{ $card->id }}"
                                                    onchange="updateSelectedCount()"
                                                >
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                <div class="text-sm leading-5 text-gray-900 dark:text-gray-100">{{ $card->id }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                @if($card->image_url)
                                                    <img src="{{ $card->image_url }}" alt="{{ $card->search_term }}" class="h-16 w-16 object-contain">
                                                @else
                                                    <div class="h-16 w-16 bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs text-gray-500">No Image</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                <div class="text-sm leading-5 text-gray-900 dark:text-gray-100">{{ $card->search_term }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                <div class="text-sm leading-5 text-gray-900 dark:text-gray-100">
                                                    {!! $card->psa_title ?? '<span class="text-gray-400">Not set</span>' !!}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $card->excluded_from_sniping ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                                    {{ $card->excluded_from_sniping ? 'Excluded' : 'Active' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700 text-sm font-medium">
                                                <div class="flex gap-2">
                                                    <button
                                                        onclick="openEditModal({{ $card->id }}, '{{ addslashes($card->search_term) }}', '{{ addslashes($card->psa_title ?? '') }}', {{ $card->excluded_from_sniping ? 'true' : 'false' }}, '{{ $card->image_url ?? '' }}', {{ $card->additional_bid ?? 1 }})"
                                                        class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-semibold px-3 py-1 rounded text-xs transition"
                                                    >
                                                        Edit
                                                    </button>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('cards.psa-title.toggle-excluded', $card) }}"
                                                        class="inline"
                                                    >
                                                        @csrf
                                                        @php
                                                            $hideWithTitle = request('hide_with_title', '1');
                                                            $hideExcluded = request('hide_excluded', '1');
                                                        @endphp
                                                        <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                                                        <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">
                                                        <button
                                                            type="submit"
                                                            class="@if($card->excluded_from_sniping) bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 @else bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 @endif text-white font-semibold px-3 py-1 rounded text-xs transition"
                                                        >
                                                            {{ $card->excluded_from_sniping ? 'Include' : 'Exclude' }}
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
                            {{ $cards->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">No cards found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <x-modal name="edit-card-modal" maxWidth="2xl">
        <div class="p-6">
            <div class="mb-6">
                <h2 class="text-2xl font-bold mb-2">Edit Card PSA Title</h2>
                <p class="text-gray-600 dark:text-gray-400" id="modal-card-info">Card ID: <span id="modal-card-id"></span> | Search Term: <span id="modal-search-term"></span></p>
            </div>

            <div class="mb-6 flex gap-6">
                <div class="flex-shrink-0">
                    <div id="modal-image-container" class="h-48 w-48 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                        <img id="modal-card-image" src="" alt="Card image" class="max-h-48 max-w-48 object-contain" style="display: none;">
                        <span id="modal-no-image" class="text-gray-500 text-sm">No Image</span>
                    </div>
                </div>
                <div class="flex-1">
                    <form method="POST" id="edit-card-form" action="">
                        @csrf
                        @method('PUT')
                        
                        @php
                            $hideWithTitle = request('hide_with_title', '1');
                            $hideExcluded = request('hide_excluded', '1');
                        @endphp
                        <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                        <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">

                        <div class="mb-4">
                            <label for="modal_psa_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                PSA Title
                            </label>
                            <input
                                type="text"
                                id="modal_psa_title"
                                name="psa_title"
                                placeholder="Enter PSA title to match eBay listings..."
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                This title will be used to match eBay listing titles to this card.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label for="modal_additional_bid" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Additional Bid
                            </label>
                            <input
                                type="number"
                                id="modal_additional_bid"
                                name="additional_bid"
                                step="0.01"
                                placeholder="1.00"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Additional amount to add to the bid price calculation. Can be negative. Default is 1.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="modal_excluded_from_sniping"
                                    name="excluded_from_sniping"
                                    value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    Exclude from sniping
                                </span>
                            </label>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Excluded cards will be filtered out from sniping.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Update Card
                            </button>
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </x-modal>

    <script>
        function updateFilters() {
            const hideWithTitle = document.getElementById('hide_with_title').checked ? '1' : '0';
            const hideExcluded = document.getElementById('hide_excluded').checked ? '1' : '0';
            const url = new URL(window.location.href);
            url.searchParams.set('hide_with_title', hideWithTitle);
            url.searchParams.set('hide_excluded', hideExcluded);
            // Remove search query when applying filters
            url.searchParams.delete('q');
            window.location.href = url.toString();
        }

        function openEditModal(cardId, searchTerm, psaTitle, excluded, imageUrl, additionalBid) {
            document.getElementById('modal-card-id').textContent = cardId;
            document.getElementById('modal-search-term').textContent = searchTerm;
            document.getElementById('modal_psa_title').value = psaTitle || '';
            document.getElementById('modal_additional_bid').value = additionalBid !== undefined ? additionalBid : 1;
            document.getElementById('modal_excluded_from_sniping').checked = excluded;
            document.getElementById('edit-card-form').action = `/cards/psa-title/${cardId}`;
            
            const img = document.getElementById('modal-card-image');
            const noImage = document.getElementById('modal-no-image');
            if (imageUrl && imageUrl.trim() !== '') {
                img.src = imageUrl;
                img.style.display = 'block';
                noImage.style.display = 'none';
            } else {
                img.style.display = 'none';
                noImage.style.display = 'block';
            }
            
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'edit-card-modal' }));
        }

        // Initialize filter checkboxes from URL params
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hideWithTitle = urlParams.get('hide_with_title');
            const hideExcluded = urlParams.get('hide_excluded');
            
            if (hideWithTitle !== null) {
                document.getElementById('hide_with_title').checked = hideWithTitle === '1';
            }
            if (hideExcluded !== null) {
                document.getElementById('hide_excluded').checked = hideExcluded === '1';
            }
            
            updateSelectedCount();
        });

        function toggleSelectAll(checked) {
            const checkboxes = document.querySelectorAll('.card-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.card-checkbox:checked');
            const count = checkboxes.length;
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);
            
            document.getElementById('selected-count').textContent = count;
            
            // Update hidden inputs for form submission
            const container = document.getElementById('selected-card-ids-container');
            container.innerHTML = '';
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'card_ids[]';
                input.value = id;
                container.appendChild(input);
            });
            
            const bulkExcludeBtn = document.getElementById('bulk-exclude-btn');
            if (count > 0) {
                bulkExcludeBtn.disabled = false;
            } else {
                bulkExcludeBtn.disabled = true;
            }
            
            // Update select all checkbox state
            const selectAllCheckbox = document.getElementById('select-all');
            const allCheckboxes = document.querySelectorAll('.card-checkbox');
            if (allCheckboxes.length > 0) {
                selectAllCheckbox.checked = count === allCheckboxes.length;
                selectAllCheckbox.indeterminate = count > 0 && count < allCheckboxes.length;
            }
        }
    </script>
</x-app-layout>

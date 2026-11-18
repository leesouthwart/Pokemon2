@php
    $hideWithTitle = request('hide_with_title', '1');
    $hideExcluded = request('hide_excluded', '1');
@endphp

<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Sniping" title="Listing titles">
            <a href="{{ route('psa-japanese-api-listings') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> API listings</a>
        </x-page-header>

        <div class="mt-6 flex flex-wrap items-center gap-3 text-sm">
            <form method="GET" action="{{ route('cards.psa-title.search') }}" id="searchForm" class="flex flex-1 flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-96">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-violet-200/50"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ $query ?? '' }}"
                        placeholder="Search by PSA title or search term…"
                        class="dh-input pl-10"
                        onkeyup="if(event.key === 'Enter') this.form.submit()"
                    >
                </div>
                <button type="submit" class="dh-btn-ghost">Search</button>
                @if(isset($query))
                    @php
                        $clearUrl = route('cards.psa-title.index');
                        $params = array_filter([
                            'hide_with_title' => request('hide_with_title'),
                            'hide_excluded' => request('hide_excluded'),
                        ], fn ($value) => $value !== null);
                        if (!empty($params)) {
                            $clearUrl .= '?' . http_build_query($params);
                        }
                    @endphp
                    <a href="{{ $clearUrl }}" class="dh-chip dh-chip-active">"{{ $query }}" <i class="fas fa-xmark text-xs"></i></a>
                @endif
            </form>

            <label class="dh-chip cursor-pointer">
                <input type="checkbox" id="hide_with_title" checked onchange="updateFilters()" class="dh-check">
                Hide cards with a title
            </label>
            <label class="dh-chip cursor-pointer">
                <input type="checkbox" id="hide_excluded" checked onchange="updateFilters()" class="dh-check">
                Hide excluded
            </label>
        </div>

        @if(session('success'))
            <div class="dh-alert-success mt-6">{{ session('success') }}</div>
        @endif

        @if($cards->count() > 0)
            <div class="dh-panel mt-6 overflow-x-auto">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3">
                    <span class="text-sm dh-muted tnum">{{ $cards->total() }} cards</span>
                    <form method="POST" action="{{ route('cards.psa-title.bulk-exclude') }}" id="bulk-exclude-form" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                        <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">
                        <div id="selected-card-ids-container"></div>
                        <button type="submit" id="bulk-exclude-btn" disabled class="dh-btn-danger dh-btn-sm">
                            <i class="fas fa-ban"></i> Exclude selected (<span id="selected-count">0</span>)
                        </button>
                    </form>
                </div>

                <table class="dh-table">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" id="select-all" class="dh-check" onchange="toggleSelectAll(this.checked)">
                            </th>
                            <th>Card</th>
                            <th>PSA title</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cards as $card)
                            <tr class="{{ $card->excluded_from_sniping ? 'opacity-50' : '' }}">
                                <td>
                                    <input type="checkbox" class="card-checkbox dh-check" value="{{ $card->id }}" onchange="updateSelectedCount()">
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($card->image_url)
                                            <img src="{{ $card->image_url }}" alt="{{ $card->search_term }}" loading="lazy" class="h-14 w-10 shrink-0 rounded-md bg-black/20 object-contain">
                                        @else
                                            <div class="grid h-14 w-10 shrink-0 place-items-center rounded-md bg-white/5 text-violet-200/30"><i class="fas fa-image"></i></div>
                                        @endif
                                        <div>
                                            <div class="font-medium text-white">{{ $card->search_term }}</div>
                                            <div class="text-xs dh-muted">#{{ $card->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-md">
                                    @if($card->psa_title)
                                        <span class="text-white">{{ $card->psa_title }}</span>
                                    @else
                                        <span class="dh-muted">Not set</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="dh-badge {{ $card->excluded_from_sniping ? 'dh-badge-red' : 'dh-badge-green' }}">
                                        {{ $card->excluded_from_sniping ? 'Excluded' : 'Active' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <button
                                            onclick="openEditModal({{ $card->id }}, '{{ addslashes($card->search_term) }}', '{{ addslashes($card->psa_title ?? '') }}', {{ $card->excluded_from_sniping ? 'true' : 'false' }}, '{{ $card->image_url ?? '' }}', {{ $card->additional_bid ?? 1 }})"
                                            class="dh-btn-ghost dh-btn-sm"
                                        >
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('cards.psa-title.toggle-excluded', $card) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                                            <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">
                                            <button type="submit" class="{{ $card->excluded_from_sniping ? 'dh-btn-success' : 'dh-btn-danger' }} dh-btn-sm">
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

            <div class="mt-8">
                {{ $cards->links() }}
            </div>
        @else
            <div class="dh-panel mt-6 py-16 text-center">
                <i class="fas fa-tags text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No cards found.</p>
            </div>
        @endif
    </div>

    <x-modal name="edit-card-modal" maxWidth="2xl">
        <div class="p-6">
            <div class="mb-6">
                <div class="dh-eyebrow">Edit listing title</div>
                <p class="mt-1 text-white" id="modal-card-info"><span id="modal-search-term"></span> <span class="dh-muted">· #<span id="modal-card-id"></span></span></p>
            </div>

            <div class="flex flex-col gap-6 sm:flex-row">
                <div class="shrink-0">
                    <div id="modal-image-container" class="grid h-48 w-36 place-items-center overflow-hidden rounded-xl bg-black/20">
                        <img id="modal-card-image" src="" alt="Card image" class="h-full w-full object-contain" style="display: none;">
                        <span id="modal-no-image" class="text-sm dh-muted">No image</span>
                    </div>
                </div>
                <div class="flex-1">
                    <form method="POST" id="edit-card-form" action="" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="hide_with_title" value="{{ $hideWithTitle }}">
                        <input type="hidden" name="hide_excluded" value="{{ $hideExcluded }}">

                        <div>
                            <label for="modal_psa_title" class="dh-label">PSA title</label>
                            <input type="text" id="modal_psa_title" name="psa_title" placeholder="Enter PSA title to match eBay listings…" class="dh-input">
                            <p class="dh-help">Used to match eBay listing titles to this card.</p>
                        </div>

                        <div>
                            <label for="modal_additional_bid" class="dh-label">Additional bid</label>
                            <input type="number" id="modal_additional_bid" name="additional_bid" step="0.01" placeholder="1.00" class="dh-input">
                            <p class="dh-help">Added to the bid price calculation. Can be negative. Default is 1.</p>
                        </div>

                        <div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" id="modal_excluded_from_sniping" name="excluded_from_sniping" value="1" class="dh-check">
                                Exclude from sniping
                            </label>
                            <p class="dh-help">Excluded cards are filtered out from sniping.</p>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <button type="submit" class="dh-btn-primary">Update card</button>
                            <button type="button" x-on:click="$dispatch('close')" class="dh-btn-ghost">Cancel</button>
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

            const countEl = document.getElementById('selected-count');
            if (!countEl) {
                return;
            }
            countEl.textContent = count;

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

            document.getElementById('bulk-exclude-btn').disabled = count === 0;

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

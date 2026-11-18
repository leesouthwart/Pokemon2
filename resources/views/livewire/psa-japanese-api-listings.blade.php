@php
    $hasListings = !empty($listings) && !(is_object($listings) && $listings->isEmpty());
@endphp

<div class="dh-page">
    <x-page-header eyebrow="Sniping · PSA 10 on the PSA account" title="PSA API listings">
        <a href="{{ route('psa-japanese-auctions') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> Pending bids</a>
        <a href="{{ route('cards.psa-title.index') }}" class="dh-btn-ghost"><i class="fas fa-tags"></i> PSA titles</a>
        <button wire:click="fetchListings" class="dh-btn-primary" @if($loading) disabled @endif>
            <i class="fas fa-rotate {{ $loading ? 'fa-spin' : '' }}"></i> {{ $loading ? 'Loading…' : 'Refresh' }}
        </button>
    </x-page-header>

    @include('livewire.partials.listing-filters')

    @include('livewire.partials.auction-states')

    @if($hasListings)
        <p class="mt-4 text-xs dh-muted"><i class="fas fa-circle-info mr-1"></i>Click a listing without a card match to link it to a card.</p>
        <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($listings as $listing)
                @include('livewire.partials.auction-tile', [
                    'listing' => $listing,
                    'showStatus' => true,
                    'clickable' => !($listing['hasMatchingCard'] ?? false),
                ])
            @endforeach
        </div>

        @if($listings->total() > 20)
            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    @endif

    @if($showModal && $selectedListing)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" wire:click="closeModal">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl border border-white/10 bg-ink-800 shadow-2xl shadow-black/60" wire:click.stop>
                <div class="flex items-start justify-between gap-4 border-b border-white/10 px-6 py-5">
                    <div class="min-w-0">
                        <div class="dh-eyebrow">Link card to listing</div>
                        <p class="mt-1 text-white">{{ $selectedListing['title'] }}</p>
                    </div>
                    <button wire:click="closeModal" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-white/10 transition hover:bg-white/5 hover:text-white">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-5 px-6 py-5">
                    @if($modalMessage)
                        <div class="{{ $modalMessageType === 'success' ? 'dh-alert-success' : 'dh-alert-error' }}">{{ $modalMessage }}</div>
                    @endif

                    <div class="dh-segment w-fit">
                        <button wire:click="$set('activeTab', 'create')" class="{{ $activeTab === 'create' ? 'is-active' : '' }}">Create new card</button>
                        <button wire:click="$set('activeTab', 'search')" class="{{ $activeTab === 'search' ? 'is-active' : '' }}">Search existing</button>
                    </div>

                    @if($activeTab === 'create')
                        <form wire:submit.prevent="createNewCard" class="space-y-4">
                            <div>
                                <label class="dh-label">Search term</label>
                                <input type="text" wire:model="newCardSearchTerm" class="dh-input" placeholder="Enter card search term" required>
                                @error('newCardSearchTerm')
                                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="dh-label">Cardrush URL</label>
                                <input type="url" wire:model="newCardUrl" class="dh-input" placeholder="Enter card URL" required>
                                @error('newCardUrl')
                                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end gap-2 border-t border-white/10 pt-4">
                                <button type="button" wire:click="closeModal" class="dh-btn-ghost">Cancel</button>
                                <button type="submit" class="dh-btn-primary" @if($creatingCard) disabled @endif>
                                    {{ $creatingCard ? 'Creating…' : 'Create card' }}
                                </button>
                            </div>
                        </form>
                    @endif

                    @if($activeTab === 'search')
                        <div class="space-y-4">
                            <div class="relative">
                                <i class="fas fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-violet-200/50"></i>
                                <input type="text" wire:model.live.debounce.300ms="cardSearchQuery" class="dh-input pl-10" placeholder="Search by search term or PSA title…">
                            </div>

                            @if($selectedCardId && !empty($selectedCardPsaTitles))
                                <div class="rounded-2xl bg-white/5 p-4">
                                    <p class="mb-2 text-sm font-medium text-white">Existing PSA titles for the selected card</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($selectedCardPsaTitles as $title)
                                            <span class="dh-badge dh-badge-gray font-normal">{{ $title }}</span>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs dh-muted">The listing title will be added as a new PSA title if it doesn't already exist.</p>
                                </div>
                            @endif

                            @if(strlen($cardSearchQuery) >= 2)
                                @if(count($searchResults) > 0)
                                    <div class="max-h-96 space-y-2 overflow-y-auto pr-1">
                                        @foreach($searchResults as $result)
                                            <div wire:click="selectCard({{ $result['id'] }})"
                                                 class="flex cursor-pointer items-center gap-3 rounded-2xl border p-3 transition {{ $selectedCardId == $result['id'] ? 'border-yellow-400/60 bg-yellow-400/10' : 'border-white/10 bg-white/[0.03] hover:bg-white/[0.06]' }}">
                                                @if($result['image_url'])
                                                    <img src="{{ $result['image_url'] }}" alt="{{ $result['search_term'] }}" class="h-16 w-12 shrink-0 rounded-md bg-black/20 object-contain">
                                                @else
                                                    <div class="grid h-16 w-12 shrink-0 place-items-center rounded-md bg-white/5 text-violet-200/30"><i class="fas fa-image"></i></div>
                                                @endif
                                                <div class="min-w-0 flex-1">
                                                    <p class="font-medium text-white">{{ $result['search_term'] }}</p>
                                                    @if(!empty($result['psa_titles']))
                                                        <p class="mt-1 text-xs dh-muted">PSA titles ({{ count($result['psa_titles']) }})</p>
                                                        <div class="mt-1 flex flex-wrap gap-1">
                                                            @foreach($result['psa_titles'] as $title)
                                                                <span class="dh-badge dh-badge-gray font-normal">{{ $title }}</span>
                                                            @endforeach
                                                        </div>
                                                    @elseif($result['psa_title'])
                                                        <p class="text-sm dh-muted">PSA title: {{ $result['psa_title'] }}</p>
                                                    @endif
                                                </div>
                                                @if($selectedCardId == $result['id'])
                                                    <i class="fas fa-circle-check text-lg text-yellow-400"></i>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-8 text-center dh-muted">No cards found matching "{{ $cardSearchQuery }}"</div>
                                @endif
                            @else
                                <div class="py-8 text-center dh-muted">Type at least 2 characters to search</div>
                            @endif

                            <div class="flex justify-end gap-2 border-t border-white/10 pt-4">
                                <button wire:click="closeModal" class="dh-btn-ghost">Cancel</button>
                                <button wire:click="linkExistingCard" @if(!$selectedCardId) disabled @endif class="dh-btn-primary">Confirm link</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

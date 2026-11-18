@php
    $isRaw = $mode === 'raw';
    $activeGroup = $groupId ? $groups->firstWhere('id', $groupId) : null;
    $sortIcon = fn ($field) => $sortField === $field
        ? '<i class="fas fa-arrow-' . ($sortDirection === 'asc' ? 'up' : 'down') . ' ml-1 text-yellow-400"></i>'
        : '';
@endphp

<div class="dh-page" x-data="{ adding: false }">
    <x-page-header
        :eyebrow="$cardGroup ? 'Card group' : ($isRaw ? 'Cardrush → eBay (ungraded)' : 'Cardrush → eBay')"
        :title="$cardGroup ? $cardGroup->name : ($isRaw ? 'Raw flips' : 'Find your next flip')"
    >
        @unless($cardGroup)
            <x-slot name="beside">
                <div class="dh-segment">
                    <a href="{{ route('cardrush') }}" class="{{ $isRaw ? '' : 'is-active' }}">PSA 10</a>
                    <a href="{{ route('cardrush-raw') }}" class="{{ $isRaw ? 'is-active' : '' }}">Raw</a>
                </div>
            </x-slot>
        @endunless

        <div class="dh-segment">
            <button type="button" wire:click="setDisplay('grid')" class="!px-3 {{ $display === 'grid' ? '!bg-white/15 !text-white' : '' }}" title="Gallery view"><i class="fas fa-grip"></i></button>
            <button type="button" wire:click="setDisplay('list')" class="!px-3 {{ $display === 'list' ? '!bg-white/15 !text-white' : '' }}" title="List view"><i class="fas fa-list"></i></button>
        </div>

        @if(!$isRaw && !$cardGroup)
            <button type="button" @click="adding = !adding" class="dh-btn-ghost">
                <i class="fas" :class="adding ? 'fa-xmark' : 'fa-plus'"></i> Add card
            </button>
        @endif

        <livewire:make-me-money :groups="$groups" :mode="$mode"/>
    </x-page-header>

    @if(!$isRaw && !$cardGroup)
        <div x-cloak x-show="adding" x-transition class="mt-6">
            <livewire:card-form />
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
        <div class="relative w-full sm:w-72">
            <i class="fas fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs text-violet-200/50"></i>
            <input type="text" wire:model.live.debounce.300ms="search" class="dh-input pl-10" placeholder="Search cards">
        </div>

        <div class="flex items-center gap-1 rounded-full border border-white/10 px-1">
            <span class="pl-3 text-violet-200/50">{{ $currency->symbol ?? '£' }}</span>
            <input type="text" wire:model.live.debounce.400ms="minPrice" class="w-16 border-0 bg-transparent px-1 py-2 text-sm text-white placeholder:text-violet-200/40 focus:ring-0" placeholder="min">
            <span class="text-violet-200/30">–</span>
            <input type="text" wire:model.live.debounce.400ms="maxPrice" class="w-16 border-0 bg-transparent px-1 py-2 text-sm text-white placeholder:text-violet-200/40 focus:ring-0" placeholder="max">
        </div>

        @unless($cardGroup)
            <select wire:change="setGroup($event.target.value)" class="dh-select w-auto {{ $activeGroup ? '!border-yellow-400/40 !bg-yellow-400/10 !text-yellow-300' : '' }}">
                <option value="" @selected(!$groupId)>All groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" @selected($groupId == $group->id)>{{ $group->name }}</option>
                @endforeach
            </select>
            @if($activeGroup)
                <button type="button" wire:click="setGroup(null)" class="dh-chip dh-chip-active">
                    {{ $activeGroup->name }} <i class="fas fa-xmark text-xs"></i>
                </button>
            @endif
        @endunless

        <div class="ml-auto flex items-center gap-2">
            <span class="hidden dh-muted md:inline">{{ number_format($cardList->total()) }} cards · sort</span>
            <select wire:model.live="sortField" class="dh-select w-auto">
                @foreach($this->sortOptions() as $field => $label)
                    <option value="{{ $field }}">{{ $label }}</option>
                @endforeach
                @unless(array_key_exists($sortField, $this->sortOptions()))
                    <option value="{{ $sortField }}">Custom</option>
                @endunless
            </select>
            <button type="button" wire:click="toggleSortDirection" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 transition hover:bg-white/5 hover:text-white" title="Toggle direction">
                <i class="fas {{ $sortDirection === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short' }}"></i>
            </button>
        </div>
    </div>

    <div wire:loading.delay.class="opacity-60" class="transition-opacity">
        @if($cardList->isEmpty())
            <div class="dh-panel mt-6 py-16 text-center">
                <i class="fas fa-box-open text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No cards match those filters.</p>
            </div>
        @elseif($display === 'grid')
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                @foreach($cardList as $card)
                    <livewire:card :card="$card" :mode="$mode" display="grid" :key="$card->id . '-' . $mode . '-grid'" />
                @endforeach
            </div>
        @else
            <div class="dh-panel mt-6 overflow-x-auto">
                <table class="dh-table whitespace-nowrap">
                    <thead>
                    <tr>
                        <th></th>
                        <th class="dh-th-sort" wire:click="sortBy('search_term')">Card {!! $sortIcon('search_term') !!}</th>
                        <th class="dh-th-sort text-right" wire:click="sortBy('cr_price')">Buy {!! $sortIcon('cr_price') !!}</th>
                        @if(!$isRaw)
                            <th class="text-right">Max bid (USD)</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('region_cards.psa_10_price')">PSA 10 low {!! $sortIcon('region_cards.psa_10_price') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('region_cards.average_psa_10_price')">PSA 10 avg {!! $sortIcon('region_cards.average_psa_10_price') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('cards.roi_average')">ROI {!! $sortIcon('cards.roi_average') . $sortIcon('roi_average') !!}</th>
                        @else
                            <th class="dh-th-sort text-right" wire:click="sortBy('region_cards.raw_price')">Raw low {!! $sortIcon('region_cards.raw_price') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('region_cards.average_raw_price')">Raw avg {!! $sortIcon('region_cards.average_raw_price') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('cards.raw_roi_average')">Raw ROI {!! $sortIcon('cards.raw_roi_average') . $sortIcon('raw_roi_average') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('region_cards.psa_10_price')">PSA 10 low {!! $sortIcon('region_cards.psa_10_price') !!}</th>
                            <th class="dh-th-sort text-right" wire:click="sortBy('cards.roi_average')">Graded ROI {!! $sortIcon('cards.roi_average') !!}</th>
                        @endif
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cardList as $card)
                        <livewire:card :card="$card" :mode="$mode" display="list" :key="$card->id . '-' . $mode . '-list'" />
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mt-8">
        {{ $cardList->links() }}
    </div>

    @if(!empty($selectedCards))
        <div class="fixed inset-x-0 bottom-6 z-30 flex justify-center px-4">
            <div class="flex flex-wrap items-center gap-2 rounded-full border border-white/10 bg-ink-800/95 py-2 pl-5 pr-2 text-sm shadow-2xl shadow-black/60 backdrop-blur">
                <span class="font-medium text-white tnum">{{ count($selectedCards) }} selected</span>
                <button type="button" wire:click="clearSelection" class="text-xs dh-muted hover:text-white">Clear</button>
                <span class="mx-1 h-5 w-px bg-white/10"></span>
                <select class="dh-select dh-input-sm w-auto" wire:model="selectedCardGroupId">
                    <option disabled value="0">Choose group…</option>
                    @foreach($groups as $cardGroupOption)
                        <option value="{{ $cardGroupOption->id }}">{{ $cardGroupOption->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="dh-btn-violet dh-btn-sm" wire:click="addToGroup"><i class="fas fa-folder-plus"></i> Add to group</button>
                <button type="button" class="dh-btn-danger dh-btn-sm" wire:click="delete" wire:confirm="Delete {{ count($selectedCards) }} card(s)?"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    @endif
</div>

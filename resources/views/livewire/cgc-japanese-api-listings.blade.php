@php
    $hasListings = !empty($listings) && !(is_object($listings) && $listings->isEmpty());
@endphp

<div class="dh-page">
    <x-page-header eyebrow="Sniping · CGC 10 on the PSA account" title="CGC API listings">
        <a href="{{ route('cgc-japanese-auctions') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> Pending bids</a>
        <a href="{{ route('cards.psa-title.index') }}" class="dh-btn-ghost"><i class="fas fa-tags"></i> Listing titles</a>
        <button wire:click="fetchListings" class="dh-btn-primary" @if($loading) disabled @endif>
            <i class="fas fa-rotate {{ $loading ? 'fa-spin' : '' }}"></i> {{ $loading ? 'Loading…' : 'Refresh' }}
        </button>
    </x-page-header>

    @include('livewire.partials.listing-filters')

    @include('livewire.partials.auction-states')

    @if($hasListings)
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($listings as $listing)
                @include('livewire.partials.auction-tile', ['listing' => $listing, 'showStatus' => true])
            @endforeach
        </div>

        @if($listings->total() > 20)
            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    @endif
</div>

@php
    $hasListings = !empty($listings) && !(is_object($listings) && $listings->isEmpty());
@endphp

<div class="dh-page">
    <x-page-header eyebrow="Sniping · ending in 24 hours" title="PSA 10 auctions">
        <x-slot name="beside">
            <div class="dh-segment">
                <a href="{{ route('psa-japanese-auctions') }}" class="is-active">PSA 10</a>
                <a href="{{ route('cgc-japanese-auctions') }}">CGC 10</a>
            </div>
        </x-slot>

        <a href="{{ route('psa-japanese-api-listings') }}" class="dh-btn-ghost"><i class="fas fa-satellite-dish"></i> API listings</a>
        <a href="{{ route('cards.psa-title.index') }}" class="dh-btn-ghost"><i class="fas fa-tags"></i> PSA titles</a>
        <button wire:click="fetchListings" class="dh-btn-primary" @if($loading) disabled @endif>
            <i class="fas fa-rotate {{ $loading ? 'fa-spin' : '' }}"></i> {{ $loading ? 'Loading…' : 'Refresh' }}
        </button>
    </x-page-header>

    @include('livewire.partials.auction-states')

    @if($hasListings)
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach($listings as $listing)
                @include('livewire.partials.auction-tile', ['listing' => $listing])
            @endforeach
        </div>

        @if($listings->total() > 20)
            <div class="mt-8">{{ $listings->links() }}</div>
        @endif
    @endif
</div>

@php
    $pricingMode = $buylist->pricing_mode ?? 'graded';
    $cardCount = collect($groupedCards)->sum(fn ($group) => count($group['cards']));
@endphp

<x-app-layout>
    <div class="dh-page">
        <x-page-header :eyebrow="$pricingMode === 'raw' ? 'Raw buylist' : 'PSA 10 buylist'" :title="$buylist->name">
            <x-slot name="beside">
                <span class="dh-badge dh-badge-gray mb-2">{{ $cardCount }} in stock · {{ $buylist->created_at?->format('j M Y') }}</span>
            </x-slot>
            <a href="{{ route('buylist.index') }}" class="dh-btn-ghost"><i class="fas fa-arrow-left"></i> All buylists</a>
        </x-page-header>

        @forelse($groupedCards as $group)
            <section class="mt-10">
                <div class="mb-4 flex items-center gap-3">
                    <h2 class="dh-h2">{{ $group['name'] }}</h2>
                    <span class="dh-badge dh-badge-yellow">{{ count($group['cards']) }}</span>
                    <div class="h-px flex-1 bg-white/10"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                    @foreach($group['cards'] as $card)
                        <livewire:card :card="$card" :mode="$pricingMode" display="grid" :key="$card->id . '-' . ($card->pivot->card_group_id ?? 'ungrouped')" />
                    @endforeach
                </div>
            </section>
        @empty
            <div class="dh-panel mt-8 py-16 text-center">
                <i class="fas fa-box-open text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No in-stock cards found for this buylist.</p>
            </div>
        @endforelse
    </div>

    <livewire:sidebar :mode="$pricingMode" />
</x-app-layout>

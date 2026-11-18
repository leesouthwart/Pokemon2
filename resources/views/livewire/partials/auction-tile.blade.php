@php
    $showStatus = $showStatus ?? false;
    $clickable = $clickable ?? false;
    $itemId = $listing['itemId'];
    $endsAt = !empty($listing['endDate']) ? \Carbon\Carbon::parse($listing['endDate']) : null;
    $urgent = $endsAt && $endsAt->isFuture() && $endsAt->diffInMinutes() < 120;
@endphp

<div wire:key="listing-{{ $itemId }}"
     class="group dh-tile flex flex-col {{ $clickable ? 'cursor-pointer' : '' }}"
     @if($clickable) wire:click="openModal('{{ $itemId }}')" @endif>
    <div class="relative overflow-hidden rounded-xl bg-black/20">
        <img src="{{ $listing['image'] }}" alt="{{ $listing['title'] }}" loading="lazy" class="aspect-square w-full object-contain">

        <span class="absolute left-2 top-2 rounded-full px-2.5 py-1 text-[11px] font-semibold shadow backdrop-blur {{ $urgent ? 'bg-rose-500 text-white' : 'bg-black/70 text-white' }}">
            <i class="far fa-clock mr-1"></i>{{ $endsAt ? $endsAt->diffForHumans(null, true, true) : 'N/A' }}
        </span>
        <span class="absolute right-2 top-2 rounded-full bg-yellow-400 px-2.5 py-1 text-xs font-bold text-ink tnum shadow">
            ${{ number_format($listing['currentBid'], 2) }}
        </span>
    </div>

    <a href="{{ $listing['url'] }}" target="_blank" wire:click.stop class="mt-3 line-clamp-2 px-1 text-sm leading-snug text-white transition hover:text-yellow-300" title="{{ $listing['title'] }}">
        {{ $listing['title'] }}
    </a>

    @if($showStatus)
        <div class="mt-2 flex flex-wrap gap-1.5 px-1">
            @if($listing['hasPendingBid'] ?? false)
                <span class="dh-badge dh-badge-green">
                    <i class="fas fa-crosshairs"></i> Pending
                    @if(!empty($listing['bidAmount'])) ${{ number_format($listing['bidAmount'], 0) }} @endif
                </span>
            @else
                <span class="dh-badge dh-badge-gray">No pending bid</span>
            @endif

            @if($listing['hasMatchingCard'] ?? false)
                <span class="dh-badge dh-badge-blue">Card #{{ $listing['matchingCardId'] ?? 'N/A' }}</span>
            @else
                <span class="dh-badge dh-badge-red">No card match</span>
            @endif
        </div>
    @endif

    <div class="mt-auto px-1 pt-3" wire:click.stop>
        <form wire:submit.prevent="submitBid('{{ $itemId }}')" class="flex items-center gap-2">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-violet-200/50">$</span>
                <input
                    type="number"
                    step="0.01"
                    min="{{ $listing['currentBid'] + 0.01 }}"
                    wire:model.defer="bidAmounts.{{ $itemId }}"
                    class="dh-input dh-input-sm pl-6 tnum"
                    placeholder="Amount"
                    value="{{ $bidAmounts[$itemId] ?? '' }}"
                >
            </div>
            <button type="submit" class="dh-btn-primary dh-btn-sm"><i class="fas fa-gavel"></i> Bid</button>
        </form>

        @if(isset($bidStatus[$itemId]))
            <div class="mt-2 text-xs {{ $bidStatus[$itemId]['success'] ? 'text-emerald-300' : 'text-rose-300' }}">
                {{ $bidStatus[$itemId]['message'] }}
            </div>
        @endif
    </div>
</div>

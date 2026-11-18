@php
    $gb = \App\Models\Region::GB;
    $roi = $rois[$gb] ?? 0;
    $psa10 = $psa10Prices[$gb] ?? 0;
    $raw = $rawPrices[$gb] ?? 0;
    $sym = $currency->symbol;
    $warn = '<i class="fas fa-triangle-exclamation text-rose-400"></i>';
@endphp
<div class="group dh-tile {{ $selected ? 'border-yellow-400/60 ring-2 ring-yellow-400/30' : '' }}">
    <div class="relative">
        <button type="button" wire:click="selectCard" class="block w-full overflow-hidden rounded-xl bg-black/20">
            @if($card->image_url)
                <img src="{{ $card->image_url }}" alt="{{ $card->search_term }}" loading="lazy" class="aspect-[5/7] w-full object-contain">
            @else
                <div class="grid aspect-[5/7] w-full place-items-center text-3xl text-violet-200/20"><i class="fas fa-image"></i></div>
            @endif
        </button>

        @if($roi == 0)
            <span class="absolute right-2 top-2 rounded-full bg-black/70 px-2.5 py-1 text-xs font-bold text-rose-300 shadow"><i class="fas fa-triangle-exclamation"></i></span>
        @else
            <span class="absolute right-2 top-2 rounded-full px-2.5 py-1 text-xs font-bold tnum shadow {{ $roiBadge }}">{{ $roi > 0 ? '+' : '' }}{{ round($roi) }}%</span>
        @endif

        <label class="absolute left-2 top-2 grid h-7 w-7 cursor-pointer place-items-center rounded-full bg-black/60 backdrop-blur transition {{ $selected ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }}">
            <input wire:model="selected" type="checkbox" wire:click="emitSelectedCard({{ $card->id }})" class="dh-check h-4 w-4">
        </label>

        <div class="absolute inset-x-2 bottom-2 hidden gap-1.5 group-hover:flex">
            <button type="button" wire:click="selectCard" class="flex-1 rounded-lg bg-black/70 py-1.5 text-xs text-white backdrop-blur transition hover:bg-black/85">
                <i class="fab fa-ebay mr-1"></i>Comps
            </button>
            <a href="{{ $card->url }}" target="_blank" class="flex-1 rounded-lg bg-black/70 py-1.5 text-center text-xs text-white backdrop-blur transition hover:bg-black/85">
                <i class="fas fa-arrow-up-right-from-square mr-1"></i>Cardrush
            </a>
        </div>
    </div>

    <div class="mt-3 px-1">
        <div class="truncate font-medium leading-tight text-white" title="{{ $card->search_term }}">{{ $card->search_term }}</div>
        <div class="mt-0.5 truncate text-xs dh-muted">
            @if($mode === 'raw')
                Graded ROI {{ $gradedRoi == 0 ? '—' : $gradedRoi . '%' }}
            @elseif($bidPrice !== null)
                Max bid ${{ number_format($bidPrice, 2) }}
            @endif
            @if($card->updated_at)
                · {{ $card->updated_at->diffForHumans(null, true, true) }} ago
            @endif
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
            <div class="dh-stat">
                <div class="dh-stat-label">Buy</div>
                <div class="dh-stat-value">{{ $sym }}{{ $card->converted_price }}</div>
            </div>
            @if($mode === 'raw')
                <div class="dh-stat">
                    <div class="dh-stat-label">Raw low</div>
                    <div class="dh-stat-value">{!! $raw == 0 ? $warn : e($sym . $raw) !!}</div>
                </div>
                <div class="dh-stat">
                    <div class="dh-stat-label">Raw avg</div>
                    <div class="dh-stat-value">{!! $raw == 0 ? $warn : e($sym . ($averageRawPrices[$gb] ?? 0)) !!}</div>
                </div>
                <div class="dh-stat">
                    <div class="dh-stat-label">PSA 10</div>
                    <div class="dh-stat-value">{!! $psa10 == 0 ? $warn : e($sym . $psa10) !!}</div>
                </div>
            @else
                <div class="dh-stat">
                    <div class="dh-stat-label">PSA 10</div>
                    <div class="dh-stat-value">{!! $psa10 == 0 ? $warn : e($sym . $psa10) !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>

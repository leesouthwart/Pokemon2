@php
    $gb = \App\Models\Region::GB;
    $roi = $rois[$gb] ?? 0;
    $psa10 = $psa10Prices[$gb] ?? 0;
    $raw = $rawPrices[$gb] ?? 0;
    $sym = $currency->symbol;
    $warn = '<i class="fas fa-triangle-exclamation text-rose-400"></i>';
@endphp

<tr class="cursor-pointer {{ $selected ? 'bg-yellow-400/[0.06]' : '' }}">
    <td class="w-10">
        <input wire:model="selected" type="checkbox" wire:click="emitSelectedCard({{ $card->id }})" class="dh-check">
    </td>

    <td wire:click="selectCard">
        <div class="flex items-center gap-3">
            @if($card->image_url)
                <img src="{{ $card->image_url }}" alt="" loading="lazy" class="h-14 w-10 shrink-0 rounded-md bg-black/20 object-contain">
            @else
                <div class="grid h-14 w-10 shrink-0 place-items-center rounded-md bg-white/5 text-violet-200/20"><i class="fas fa-image"></i></div>
            @endif
            <div class="min-w-0">
                <div class="max-w-[320px] truncate font-medium text-white">{{ $card->search_term }}</div>
                @if($card->updated_at)
                    <div class="text-xs dh-muted">Updated {{ $card->updated_at->diffForHumans() }}</div>
                @endif
            </div>
        </div>
    </td>

    <td class="text-right font-semibold text-white tnum" wire:click="selectCard">{{ $sym }}{{ $card->converted_price }}</td>

    @if($mode === 'graded')
        <td class="text-right dh-muted tnum" wire:click="selectCard">
            {!! $bidPrice === null ? $warn : e('$' . number_format($bidPrice, 2)) !!}
        </td>
        <td class="text-right font-semibold text-white tnum" wire:click="selectCard">{!! $psa10 == 0 ? $warn : e($sym . $psa10) !!}</td>
        <td class="text-right dh-muted tnum" wire:click="selectCard">{!! $psa10 == 0 ? $warn : e($sym . ($averagePsa10Prices[$gb] ?? 0)) !!}</td>
        <td class="text-right" wire:click="selectCard">
            @if($roi == 0)
                {!! $warn !!}
            @else
                <span class="dh-badge {{ $roiBadge }}">{{ $roi }}%</span>
            @endif
        </td>
    @else
        <td class="text-right font-semibold text-white tnum" wire:click="selectCard">{!! $raw == 0 ? $warn : e($sym . $raw) !!}</td>
        <td class="text-right dh-muted tnum" wire:click="selectCard">{!! $raw == 0 ? $warn : e($sym . ($averageRawPrices[$gb] ?? 0)) !!}</td>
        <td class="text-right" wire:click="selectCard">
            @if($roi == 0)
                {!! $warn !!}
            @else
                <span class="dh-badge {{ $roiBadge }}">{{ $roi }}%</span>
            @endif
        </td>
        <td class="text-right dh-muted tnum" wire:click="selectCard">{!! $psa10 == 0 ? $warn : e($sym . $psa10) !!}</td>
        <td class="text-right dh-muted tnum" wire:click="selectCard">{!! $gradedRoi == 0 ? $warn : e($gradedRoi . '%') !!}</td>
    @endif

    <td class="text-right">
        <a target="_blank" href="{{ $card->url }}" class="inline-grid h-8 w-8 place-items-center rounded-full text-violet-200/50 transition hover:bg-white/5 hover:text-white">
            <i class="fas fa-arrow-up-right-from-square text-xs"></i>
        </a>
    </td>
</tr>

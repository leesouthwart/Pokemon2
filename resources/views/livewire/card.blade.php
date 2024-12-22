
<tr class="cursor-pointer">    
    <td class="pl-4">
        <input wire:model="selected" type="checkbox" wire:click="emitSelectedCard({{ $card->id }})">
    </td>

    <td class="py-4 pl-4" wire:click="selectCard">
        <div class="flex items-center">
            <img src="{{$card->image_url}}" class="aspect-[4/5] h-20">
        </div>
    </td>

    <td class="py-4 pl-4 pr-8 sm:pl-6 lg:pl-8" wire:click="selectCard">
        <div class="flex items-center">
            <div class="truncate text-sm font-medium leading-6 text-white">{{$card->search_term}}</div>
        </div>
    </td>
    <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8" wire:click="selectCard">
        <div class="flex gap-x-3">
            <div class="font-mono text-sm leading-6 text-gray-400">{{$currency->symbol}}{{$card->converted_price}}</div>
        </div>
    </td>
    <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8" wire:click="selectCard">
        <div class="flex gap-x-3">
            <div class="font-mono text-sm leading-6 text-gray-400">
                @if($psa10Prices[\App\Models\Region::GB] == 0)
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                @else
                {{$currency->symbol}}{{$psa10Prices[\App\Models\Region::GB]}}
                @endif
            </div>
        </div>
    </td>
    <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8" wire:click="selectCard">
        <div class="flex gap-x-3">
            <div class="font-mono text-sm leading-6 text-gray-400">
                @if($psa10Prices[\App\Models\Region::GB] == 0)
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                @else
                    {{$currency->symbol}}{{$averagePsa10Prices[\App\Models\Region::GB]}}
                @endif
            </div>
        </div>
    </td>
    <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8" wire:click="selectCard">
        <div class="flex gap-x-3">
            <div class="font-mono text-sm leading-6 {{$roiLowestColor}}">
                @if($rois[\App\Models\Region::GB] == 0)
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                @else
                {{$rois[\App\Models\Region::GB]}}%
                @endif
            </div>
        </div>
    </td>

    <td class="hidden py-4 pl-0 pr-4 sm:table-cell sm:pr-8">
        <a target="_blank" href="{{$card->url}}">
            <div class="font-mono text-sm leading-6 text-gray-400">
                <i class="fas fa-external-link-alt"></i>
            </div>
        </a>
    </td>
</tr>

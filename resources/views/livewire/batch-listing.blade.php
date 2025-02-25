<div class="flex {{$price == '-0.05' ? 'bg-red-300' : ''}}">
    <div class="w-full m-2">
        <input class="w-full" type="text" wire:model.live="title">
    </div>

    <div class="w-1/3 m-2">
        <input class="w-full" type="text" wire:model.live="quantity">
    </div>

    <div class="w-1/3 m-2 relative">
        <input class="w-full" type="text" wire:model.live="price" placeholder="Enter Price">
        <div class="absolute top-[14px] right-0">
            <p class="ebay_fee_text text-gray-500">{{$currency->symbol}}{{$afterFees}}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-[1fr_90px_140px] items-center gap-3 px-4 py-2 {{ $price == '-0.05' ? 'bg-rose-500/10' : '' }}">
    <input class="dh-input dh-input-sm" type="text" wire:model.live="title">

    <input class="dh-input dh-input-sm tnum" type="text" wire:model.live="quantity">

    <div class="relative">
        <input class="dh-input dh-input-sm pr-16 tnum" type="text" wire:model.live="price" placeholder="Price">
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[11px] dh-muted tnum" title="After eBay fees">{{ $currency->symbol }}{{ $afterFees }}</span>
    </div>
</div>

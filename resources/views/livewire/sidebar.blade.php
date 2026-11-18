<div x-data="{ open: $wire.entangle('show') }" x-on:card-selected.camel.window="open = true" x-on:keydown.escape.window="open = false">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px]" @click="open = false"></div>

    <aside x-cloak class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col border-l border-white/10 bg-ink-800/95 shadow-2xl shadow-black/60 backdrop-blur transition-transform duration-300" :class="open ? 'translate-x-0' : 'translate-x-full'">
        <div class="flex items-start gap-4 border-b border-white/10 p-5">
            @if($card)
                @if($card->image_url)
                    <img src="{{ $card->image_url }}" alt="" class="h-24 w-[68px] shrink-0 rounded-lg bg-black/20 object-contain">
                @endif
                <div class="min-w-0 flex-1">
                    <div class="dh-eyebrow">{{ $mode === 'raw' ? 'Live raw listings' : 'Live PSA 10 listings' }}</div>
                    <div class="mt-1 font-semibold leading-tight text-white">{{ $card->search_term }}</div>
                    <a href="{{ $card->url }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs dh-muted hover:text-yellow-300">
                        Open on Cardrush <i class="fas fa-arrow-up-right-from-square text-[10px]"></i>
                    </a>
                </div>
            @else
                <div class="flex-1 font-semibold text-white">Current listings</div>
            @endif
            <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-white/10 transition hover:bg-white/5 hover:text-white" @click="open = false">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <div class="flex-1 space-y-3 overflow-y-auto p-5">
            <div wire:loading class="w-full py-10 text-center dh-muted">
                <i class="fas fa-circle-notch fa-spin text-2xl text-yellow-400"></i>
            </div>

            @if($card && !empty($ebayData[$card->id]))
                @foreach($ebayData[$card->id] as $listingData)
                    <a href="{{ $listingData['url'] }}" target="_blank" class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition hover:bg-white/[0.07]">
                        <img class="h-28 w-20 shrink-0 rounded-lg bg-black/20 object-cover" src="{{ $listingData['image'] }}" alt="">
                        <div class="flex min-w-0 flex-1 flex-col">
                            <p class="line-clamp-3 text-sm leading-snug text-white">{{ $listingData['title'] }}</p>
                            <p class="mt-1 truncate text-xs dh-muted">
                                {{ $listingData['seller']['username'] }} · <i class="fas fa-star text-[9px] text-yellow-400"></i>
                                {{ $listingData['seller']['feedbackScore'] }} ({{ $listingData['seller']['feedbackPercentage'] }}%)
                            </p>
                            <div class="mt-auto flex items-end justify-between pt-2">
                                <span class="text-lg font-semibold text-white tnum">
                                    @if($currency->id != \App\Models\Currency::GBP)
                                        {{ $currency->symbol }}{{ $listingData['price'] * $currency->convertTo->where('id', $currency->id)->first()->pivot->conversion_rate }}
                                    @else
                                        {{ $currency->symbol }}{{ $listingData['price'] }}
                                    @endif
                                </span>
                                <span class="text-xs dh-muted"><i class="fab fa-ebay text-base"></i></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            @elseif($card)
                <div wire:loading.remove class="rounded-2xl border border-dashed border-white/10 py-10 text-center text-sm dh-muted">No listings found.</div>
            @endif
        </div>
    </aside>
</div>

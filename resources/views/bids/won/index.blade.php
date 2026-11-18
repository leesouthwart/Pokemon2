<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Sniping" title="Won bids">
            <x-slot name="beside">
                <span class="dh-badge dh-badge-yellow mb-2">{{ $bids->total() }} awaiting confirmation</span>
            </x-slot>
        </x-page-header>

        <div class="mt-6 space-y-3">
            @if(session('success'))
                <div class="dh-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="dh-alert-error">{{ session('error') }}</div>
            @endif
        </div>

        @if($bids->count() > 0)
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($bids as $bid)
                    <div class="dh-tile flex flex-col">
                        <div class="flex gap-3">
                            @if($bid->card && $bid->card->image_url)
                                <img src="{{ $bid->card->image_url }}" alt="" class="h-28 w-20 shrink-0 rounded-lg bg-black/20 object-contain">
                            @else
                                <div class="grid h-28 w-20 shrink-0 place-items-center rounded-lg bg-white/5 text-violet-200/30"><i class="fas fa-trophy text-xl"></i></div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-3 text-sm leading-snug text-white" title="{{ $bid->ebay_title }}">{{ $bid->ebay_title }}</p>
                                @if($bid->card)
                                    <p class="mt-1 text-xs dh-muted">Card #{{ $bid->card->id }}</p>
                                @endif
                                <p class="mt-1 text-xs dh-muted"><i class="far fa-calendar mr-1"></i>{{ $bid->end_date ? $bid->end_date->format('j M Y, H:i') : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="dh-stat">
                                <div class="dh-stat-label">Our max bid</div>
                                <div class="dh-stat-value">${{ number_format($bid->bid_amount, 2) }}</div>
                            </div>
                            <div class="dh-stat">
                                <div class="dh-stat-label">End price</div>
                                <div class="dh-stat-value text-yellow-300">${{ number_format($bid->end_price, 2) }}</div>
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <form action="{{ route('bids.won.confirm', $bid->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="dh-btn-success w-full"><i class="fas fa-check"></i> Confirm</button>
                            </form>
                            <form action="{{ route('bids.won.decline', $bid->id) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="dh-btn-danger w-full"><i class="fas fa-xmark"></i> Decline</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $bids->links() }}
            </div>
        @else
            <div class="dh-panel mt-6 py-16 text-center">
                <i class="fas fa-trophy text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No won bids awaiting confirmation.</p>
            </div>
        @endif
    </div>
</x-app-layout>

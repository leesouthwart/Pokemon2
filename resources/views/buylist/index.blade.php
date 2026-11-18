<x-app-layout>
    <div class="dh-page">
        <x-page-header eyebrow="Buying" title="Buylists">
            <a href="{{ route('cardrush') }}" class="dh-btn-primary"><i class="fas fa-bolt"></i> Make a new one</a>
        </x-page-header>

        @if($buylists->count() > 0)
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($buylists as $buylist)
                    <a href="{{ route('buylist.view', $buylist) }}" class="dh-tile group block p-5">
                        <div class="flex items-center justify-between">
                            <span class="dh-badge {{ ($buylist->pricing_mode ?? 'graded') === 'raw' ? 'dh-badge-violet' : 'dh-badge-yellow' }}">
                                {{ ($buylist->pricing_mode ?? 'graded') === 'raw' ? 'Raw' : 'PSA 10' }}
                            </span>
                            <i class="fas fa-arrow-right text-xs text-violet-200/30 transition group-hover:translate-x-0.5 group-hover:text-yellow-400"></i>
                        </div>
                        <div class="mt-4 truncate text-lg font-semibold text-white">{{ $buylist->name }}</div>
                        <div class="text-xs dh-muted">{{ $buylist->created_at?->format('j M Y, H:i') }}</div>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs">
                            <div class="dh-stat">
                                <div class="dh-stat-label">In stock</div>
                                <div class="dh-stat-value">{{ $buylist->in_stock_count }}</div>
                            </div>
                            <div class="dh-stat">
                                <div class="dh-stat-label">Target</div>
                                <div class="dh-stat-value">{{ $buylist->total_cards }}</div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $buylists->links() }}
            </div>
        @else
            <div class="dh-panel mt-8 py-16 text-center">
                <i class="fas fa-list-check text-3xl text-violet-200/30"></i>
                <p class="mt-3 dh-muted">No buylists yet. Hit "Make me money" on a card list to generate one.</p>
            </div>
        @endif
    </div>
</x-app-layout>

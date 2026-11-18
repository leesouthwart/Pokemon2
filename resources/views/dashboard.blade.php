@php
    $isOwner = Auth::check() && Auth::user()->email === 'leesouthwart@gmail.com';

    $tools = [
        'Arbitrage' => [
            ['route' => 'cardrush', 'title' => 'Cardrush PSA 10', 'desc' => 'Buy raw, grade, sell PSA 10. The ROI database.', 'icon' => 'fa-chart-line', 'tint' => 'from-yellow-400/30'],
            ['route' => 'cardrush-raw', 'title' => 'Cardrush Raw', 'desc' => 'Buy raw on Cardrush, sell ungraded on eBay.', 'icon' => 'fa-layer-group', 'tint' => 'from-lime-400/25'],
            ['route' => 'cards.bulk-import', 'title' => 'Bulk import', 'desc' => 'Paste search terms and links to import many cards at once.', 'icon' => 'fa-file-import', 'tint' => 'from-sky-400/25'],
        ],
        'Buying' => [
            ['route' => 'buylist.index', 'title' => 'Buylists', 'desc' => 'Every list "Make me money" has generated for you.', 'icon' => 'fa-list-check', 'tint' => 'from-emerald-400/25'],
            ['route' => 'card_group.bulk-add', 'title' => 'Card groups', 'desc' => 'Create groups and bulk-assign cards to them.', 'icon' => 'fa-folder-plus', 'tint' => 'from-pink-400/25'],
        ],
        'Sniping' => array_values(array_filter([
            $isOwner ? ['route' => 'psa-japanese-auctions', 'title' => 'PSA 10 auctions', 'desc' => 'Japanese PSA 10 auctions ending in the next 24 hours.', 'icon' => 'fa-gavel', 'tint' => 'from-violet-400/30'] : null,
            $isOwner ? ['route' => 'cgc-japanese-auctions', 'title' => 'CGC 10 auctions', 'desc' => 'CGC 10 auctions on the PSA account, bid manually.', 'icon' => 'fa-gavel', 'tint' => 'from-orange-400/25'] : null,
            ['route' => 'bids.won.index', 'title' => 'Won bids', 'desc' => 'Confirm or decline auctions you have won.', 'icon' => 'fa-trophy', 'tint' => 'from-yellow-400/25'],
        ])),
        'Selling' => [
            ['route' => 'batch.create', 'title' => 'PSA listing upload', 'desc' => 'Create eBay listings from a range of PSA cert numbers.', 'icon' => 'fa-upload', 'tint' => 'from-fuchsia-400/25'],
            ['route' => 'batch.index', 'title' => 'Batches', 'desc' => 'Review and export previous listing batches.', 'icon' => 'fa-boxes-stacked', 'tint' => 'from-indigo-400/25'],
        ],
    ];
@endphp

<x-app-layout>
    <div class="dh-page">
        <div class="dh-eyebrow">Dashboard</div>
        <h1 class="dh-h1">Welcome back{{ Auth::check() ? ', ' . strtok(Auth::user()->name, ' ') : '' }}</h1>
        <p class="mt-2 max-w-xl dh-muted">Pick up where you left off: find a flip, snipe an auction, or ship a batch.</p>

        <div class="mt-6 space-y-3">
            @if(Session::has('message'))
                <div class="dh-alert-info">{{ Session::get('message') }}</div>
            @endif

            @if(Session::has('error'))
                <div class="dh-alert-error">{{ Session::get('error') }}</div>
            @endif

            @if(!empty($ebayUsername))
                <div class="dh-alert-info flex flex-wrap items-center justify-between gap-3">
                    <span><i class="fab fa-ebay mr-2 text-lg align-middle"></i>Connected as <strong class="text-white">{{ $ebayUsername }}</strong>.</span>
                    <form action="{{ route('ebay.create-auction-listing') }}" method="POST">
                        @csrf
                        <button type="submit" class="dh-btn-ghost dh-btn-sm">Create test auction (sandbox)</button>
                    </form>
                </div>
            @endif
        </div>

        @foreach($tools as $section => $items)
            @continue(empty($items))
            <section class="mt-10">
                <div class="mb-4 flex items-center gap-3">
                    <h2 class="text-sm font-medium uppercase tracking-wider dh-muted">{{ $section }}</h2>
                    <div class="h-px flex-1 bg-white/10"></div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($items as $tool)
                        <a href="{{ route($tool['route']) }}" class="group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] p-5 transition hover:-translate-y-0.5 hover:border-white/20 hover:bg-white/[0.07]">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br {{ $tool['tint'] }} to-transparent opacity-40 transition group-hover:opacity-70"></div>
                            <div class="relative flex items-start justify-between">
                                <span class="grid h-11 w-11 place-items-center rounded-xl bg-white/10 text-lg text-white">
                                    <i class="fas {{ $tool['icon'] }}"></i>
                                </span>
                                <i class="fas fa-arrow-right text-violet-200/30 transition group-hover:translate-x-0.5 group-hover:text-yellow-400"></i>
                            </div>
                            <h3 class="relative mt-6 text-lg font-semibold text-white">{{ $tool['title'] }}</h3>
                            <p class="relative mt-1 text-sm dh-muted">{{ $tool['desc'] }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-app-layout>

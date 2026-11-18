@php
    $isOwner = Auth::check() && Auth::user()->email === 'leesouthwart@gmail.com';

    $sections = [
        [
            'label' => 'Arbitrage',
            'items' => [
                ['label' => 'PSA 10 list', 'route' => 'cardrush', 'icon' => 'fa-chart-line'],
                ['label' => 'Raw list', 'route' => 'cardrush-raw', 'icon' => 'fa-layer-group'],
                ['label' => 'Bulk import', 'route' => 'cards.bulk-import', 'icon' => 'fa-file-import'],
            ],
        ],
        [
            'label' => 'Buylists',
            'items' => [
                ['label' => 'All buylists', 'route' => 'buylist.index', 'icon' => 'fa-list-check'],
            ],
            'patterns' => ['buylist.*'],
        ],
        [
            'label' => 'Sniping',
            'items' => array_values(array_filter([
                $isOwner ? ['label' => 'PSA 10 auctions', 'route' => 'psa-japanese-auctions', 'icon' => 'fa-gavel'] : null,
                $isOwner ? ['label' => 'PSA API listings', 'route' => 'psa-japanese-api-listings', 'icon' => 'fa-satellite-dish'] : null,
                $isOwner ? ['label' => 'CGC 10 auctions', 'route' => 'cgc-japanese-auctions', 'icon' => 'fa-gavel'] : null,
                $isOwner ? ['label' => 'CGC API listings', 'route' => 'cgc-japanese-api-listings', 'icon' => 'fa-satellite-dish'] : null,
                ['label' => 'Won bids', 'route' => 'bids.won.index', 'icon' => 'fa-trophy'],
                ['label' => 'Listing titles', 'route' => 'cards.psa-title.index', 'icon' => 'fa-tags'],
            ])),
            'patterns' => ['psa-japanese-*', 'cgc-japanese-*', 'bids.won.*', 'cards.psa-title.*'],
        ],
        [
            'label' => 'Selling',
            'items' => [
                ['label' => 'PSA listing upload', 'route' => 'batch.create', 'icon' => 'fa-upload'],
                ['label' => 'Batches', 'route' => 'batch.index', 'icon' => 'fa-boxes-stacked'],
            ],
            'patterns' => ['batch.*'],
        ],
        [
            'label' => 'Groups',
            'items' => [
                ['label' => 'All groups', 'route' => 'card_group.index', 'icon' => 'fa-folder'],
                ['label' => 'Bulk add to group', 'route' => 'card_group.bulk-add', 'icon' => 'fa-folder-plus'],
            ],
            'patterns' => ['card_group.*'],
        ],
    ];

    foreach ($sections as &$section) {
        $patterns = array_merge($section['patterns'] ?? [], array_column($section['items'], 'route'));
        $section['active'] = request()->routeIs(...$patterns);
    }
    unset($section);

    $profitData = Auth::check() ? Auth::user()->calculateProfit() : null;
    $initials = Auth::check()
        ? collect(explode(' ', Auth::user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('')
        : '?';
@endphp

<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-white/10 bg-ink/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-[1400px] items-center gap-6 px-4 sm:px-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 shrink-0">
            <div class="grid h-9 w-9 place-items-center rounded-xl bg-yellow-400 font-bold text-ink">DH</div>
            <span class="hidden text-lg font-semibold text-white sm:inline">DarkHoundoom</span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex">
            @foreach($sections as $section)
                @if(count($section['items']) === 1)
                    <a href="{{ route($section['items'][0]['route']) }}" class="dh-nav-link {{ $section['active'] ? 'is-active' : '' }}">
                        {{ $section['label'] }}
                    </a>
                @else
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                        <button type="button" @click="open = !open" class="dh-nav-link {{ $section['active'] ? 'is-active' : '' }}">
                            {{ $section['label'] }}
                            <i class="fas fa-chevron-down text-[9px] opacity-60 transition" :class="open && 'rotate-180'"></i>
                        </button>
                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="dh-menu">
                            @foreach($section['items'] as $item)
                                <a href="{{ route($item['route']) }}" class="dh-menu-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                                    <i class="fas {{ $item['icon'] }}"></i>{{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2 sm:gap-3 text-sm">
            @if($profitData)
                <div class="relative group hidden sm:block">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 tnum cursor-default
                        {{ $profitData['profit'] >= 0 ? 'border-emerald-400/30 text-emerald-300' : 'border-rose-500/30 text-rose-300' }}">
                        <i class="fas fa-wallet text-yellow-400"></i>
                        {{ $profitData['profit'] < 0 ? '-' : '' }}${{ number_format(abs($profitData['profit']), 2) }}
                    </span>
                    <div class="invisible absolute right-0 top-full z-50 mt-2 w-64 rounded-2xl border border-white/10 bg-ink-800/95 p-4 text-sm opacity-0 shadow-2xl shadow-black/50 backdrop-blur transition group-hover:visible group-hover:opacity-100">
                        <div class="flex justify-between border-b border-white/10 pb-2">
                            <span class="dh-muted">Payouts</span>
                            <span class="font-semibold text-emerald-300 tnum">${{ number_format($profitData['payouts'], 2) }}</span>
                        </div>
                        <div class="flex justify-between border-b border-white/10 py-2">
                            <span class="dh-muted">Spent</span>
                            <span class="font-semibold text-rose-300 tnum">${{ number_format($profitData['spent'], 2) }}</span>
                        </div>
                        <div class="flex justify-between pt-2">
                            <span class="dh-muted">Net profit</span>
                            <span class="font-bold tnum {{ $profitData['profit'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">${{ number_format($profitData['profit'], 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('ebay.login') }}" class="hidden rounded-full border border-white/10 px-3 py-1.5 transition hover:bg-white/5 hover:text-white md:inline-flex md:items-center md:gap-1.5">
                <i class="fab fa-ebay text-base leading-none"></i> Connect
            </a>

            <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                <button type="button" @click="open = !open" class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-500 text-xs font-semibold uppercase text-white ring-2 ring-transparent transition hover:ring-white/20">
                    {{ $initials }}
                </button>
                <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="dh-menu left-auto right-0 w-56">
                    @auth
                        <div class="px-3 py-2">
                            <div class="truncate font-medium text-white">{{ Auth::user()->name }}</div>
                            <div class="truncate text-xs dh-muted">{{ Auth::user()->email }}</div>
                        </div>
                        <div class="my-1 border-t border-white/10"></div>
                        <a href="{{ route('profile.edit') }}" class="dh-menu-item"><i class="fas fa-user"></i>Profile</a>
                        <a href="{{ route('ebay.login') }}" class="dh-menu-item"><i class="fab fa-ebay"></i>Login to eBay</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dh-menu-item w-full"><i class="fas fa-arrow-right-from-bracket"></i>Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="dh-menu-item"><i class="fas fa-arrow-right-to-bracket"></i>Log in</a>
                    @endauth
                </div>
            </div>

            <button type="button" @click="mobileOpen = !mobileOpen" class="grid h-9 w-9 place-items-center rounded-full border border-white/10 lg:hidden">
                <i class="fas" :class="mobileOpen ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>
    </div>

    <div x-cloak x-show="mobileOpen" x-transition class="border-t border-white/10 px-4 py-4 lg:hidden">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($sections as $section)
                <div>
                    <div class="mb-1 px-3 text-xs font-medium uppercase tracking-wider dh-muted">{{ $section['label'] }}</div>
                    @foreach($section['items'] as $item)
                        <a href="{{ route($item['route']) }}" class="dh-menu-item {{ request()->routeIs($item['route']) ? 'is-active' : '' }}">
                            <i class="fas {{ $item['icon'] }}"></i>{{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</header>

@php
$pageBtn = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-full px-3 text-sm transition';
$idle = $pageBtn . ' border border-white/10 text-violet-100/80 hover:bg-white/5 hover:text-white';
$disabled = $pageBtn . ' border border-white/5 text-violet-200/30 cursor-default';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm dh-muted tnum">
            @if ($paginator->firstItem())
                Showing <span class="text-white">{{ $paginator->firstItem() }}</span>–<span class="text-white">{{ $paginator->lastItem() }}</span>
            @else
                {{ $paginator->count() }}
            @endif
            of <span class="text-white">{{ $paginator->total() }}</span>
        </p>

        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true"><i class="fas fa-chevron-left text-xs"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}" aria-label="{{ __('pagination.previous') }}">
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $disabled }}" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $pageBtn }} bg-yellow-400 font-semibold text-ink tnum">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $idle }} tnum" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}" aria-label="{{ __('pagination.next') }}">
                    <i class="fas fa-chevron-right text-xs"></i>
                </a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true"><i class="fas fa-chevron-right text-xs"></i></span>
            @endif
        </div>
    </nav>
@endif

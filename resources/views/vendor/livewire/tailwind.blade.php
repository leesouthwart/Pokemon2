@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';

$pageBtn = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-full px-3 text-sm transition';
$idle = $pageBtn . ' border border-white/10 text-violet-100/80 hover:bg-white/5 hover:text-white';
$disabled = $pageBtn . ' border border-white/5 text-violet-200/30 cursor-default';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm dh-muted tnum">
                Showing <span class="text-white">{{ $paginator->firstItem() }}</span>–<span class="text-white">{{ $paginator->lastItem() }}</span>
                of <span class="text-white">{{ $paginator->total() }}</span>
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabled }}" aria-disabled="true"><i class="fas fa-chevron-left text-xs"></i></span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="{{ $idle }}" aria-label="{{ __('pagination.previous') }}">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $disabled }}" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="{{ $pageBtn }} bg-yellow-400 font-semibold text-ink tnum">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="{{ $idle }} tnum" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="{{ $idle }}" aria-label="{{ __('pagination.next') }}">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true"><i class="fas fa-chevron-right text-xs"></i></span>
                @endif
            </div>
        </nav>
    @endif
</div>

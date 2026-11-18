@props(['eyebrow' => null, 'title'])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end gap-x-6 gap-y-4']) }}>
    <div>
        @if($eyebrow)
            <div class="dh-eyebrow">{{ $eyebrow }}</div>
        @endif
        <h1 class="dh-h1">{{ $title }}</h1>
    </div>

    @isset($beside)
        {{ $beside }}
    @endisset

    @if(trim($slot) !== '')
        <div class="ml-auto flex flex-wrap items-center gap-2 sm:gap-3">
            {{ $slot }}
        </div>
    @endif
</div>

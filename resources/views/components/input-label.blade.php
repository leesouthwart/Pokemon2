@props(['value'])

<label {{ $attributes->merge(['class' => 'dh-label']) }}>
    {{ $value ?? $slot }}
</label>

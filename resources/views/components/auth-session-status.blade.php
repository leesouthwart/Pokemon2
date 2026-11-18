@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'dh-alert-success']) }}>
        {{ $status }}
    </div>
@endif

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'dh-btn-danger']) }}>
    {{ $slot }}
</button>

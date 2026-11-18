<button {{ $attributes->merge(['type' => 'submit', 'class' => 'dh-btn-primary']) }}>
    {{ $slot }}
</button>

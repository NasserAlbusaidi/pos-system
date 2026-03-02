<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-secondary disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>

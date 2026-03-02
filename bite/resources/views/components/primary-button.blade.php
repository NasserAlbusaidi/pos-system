<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>

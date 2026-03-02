@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-ink-soft']) }}>
    {{ $value ?? $slot }}
</label>

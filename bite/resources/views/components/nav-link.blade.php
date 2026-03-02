@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-crema/60 bg-crema px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel transition-all duration-200'
            : 'inline-flex items-center rounded-full border border-line bg-panel px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-ink-soft transition-all duration-200 hover:border-ink hover:text-ink';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

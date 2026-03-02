@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg border border-crema/50 bg-crema px-3 py-2.5 text-start font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel transition-all duration-200'
            : 'block w-full rounded-lg border border-line bg-panel px-3 py-2.5 text-start font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-ink-soft transition-all duration-200 hover:border-ink hover:text-ink';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

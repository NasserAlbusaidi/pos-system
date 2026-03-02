@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-signal/35 bg-signal/10 px-3 py-2 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal']) }}>
        {{ $status }}
    </div>
@endif

@props(['active', 'href', 'icon', 'navigate' => true])

@php
$isActive = (bool) ($active ?? false);
$classes = $isActive
    ? 'group relative flex items-center gap-3 rounded-xl border border-crema/60 bg-crema px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel transition-all duration-200'
    : 'group relative flex items-center gap-3 rounded-xl border border-transparent px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel/65 transition-all duration-200 hover:border-panel/20 hover:bg-panel/10 hover:text-panel';
@endphp

<a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }} @if($navigate) wire:navigate @endif>
    @if(isset($icon))
        <span @class([
            'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border transition-all duration-200',
            'border-panel/20 bg-panel/15 text-panel' => $isActive,
            'border-panel/15 bg-panel/5 text-panel/60 group-hover:border-panel/25 group-hover:text-panel' => ! $isActive,
        ])>
            @switch($icon)
                @case('dashboard')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    @break
                @case('terminal')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    @break
                @case('kitchen')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                    @break
                @case('coffee')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    @break
                @case('modifiers')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                    @break
                @case('billing')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    @break
            @endswitch
        </span>
    @endif

    <span class="truncate">{{ $slot }}</span>
    @if($isActive)
        <span class="ml-auto h-2 w-2 rounded-full bg-panel/90"></span>
    @endif
</a>

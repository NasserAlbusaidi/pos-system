@props(['active', 'href', 'icon', 'navigate' => true])

@php
$isActive = (bool) ($active ?? false);
$base = 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-all duration-150';
$classes = $isActive
    ? $base . ' text-forest'
    : $base . ' text-white/70 hover:bg-white/10 hover:text-white';
@endphp

<a {{ $attributes->merge(['href' => $href, 'class' => $classes]) }}
    @if($isActive) style="background: var(--bite-lime);" @endif
    @if($navigate) wire:navigate @endif>
    @if(isset($icon))
        <span class="inline-flex h-[18px] w-[18px] shrink-0 items-center justify-center">
            @switch($icon)
                @case('dashboard')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    @break
                @case('terminal')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    @break
                @case('kitchen')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V18H6Z"/><path d="M6 17h12"/></svg>
                    @break
                @case('chart')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-6"/></svg>
                    @break
                @case('clock')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @break
                @case('log')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/></svg>
                    @break
                @case('catalog')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/></svg>
                    @break
                @case('coffee')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/><path d="M6 2v3M10 2v3M14 2v3"/></svg>
                    @break
                @case('modifiers')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 6h11M4 12h7M4 18h13"/><circle cx="18" cy="6" r="2.4"/><circle cx="14" cy="12" r="2.4"/><circle cx="20" cy="18" r="2.4"/></svg>
                    @break
                @case('tag')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59A2 2 0 0 0 3.83 11l9.58 9.59a2 2 0 0 0 2.83 0l4.35-4.35a2 2 0 0 0 0-2.83Z"/><circle cx="7.5" cy="7.5" r="1.2"/></svg>
                    @break
                @case('qr')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 21v.01M14 21h.01M21 17v.01"/></svg>
                    @break
                @case('settings')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
                    @break
                @case('billing')
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                    @break
            @endswitch
        </span>
    @endif

    <span class="truncate">{{ $slot }}</span>

    @if($navigate === false)
        <svg class="ms-auto h-[13px] w-[13px] opacity-50" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M7 17 17 7M9 7h8v8"/></svg>
    @endif
</a>

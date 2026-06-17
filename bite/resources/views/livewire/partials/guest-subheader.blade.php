{{-- Full-menu green subheader (prototype web-subheader). Replaces the cover hero
     + page header on the menu screen: back → home, "Full menu" + venue, group-order
     and language controls, and the search field. Rendered inside the
     .guest-screen--menu Alpine scope, so the search input drives the same `query`
     the list rows filter on. Expects: $shop, $locale, $this->isGroupMode. --}}
@php
    $subNextLocale = $locale === 'ar' ? 'en' : 'ar';
@endphp
<section class="guest-subheader">
    <div class="guest-subheader__row">
        <button type="button" wire:click="showHome" class="guest-subheader__icon" aria-label="{{ __('guest.back') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
        <div class="guest-subheader__titles">
            <h1 class="guest-subheader__title">{{ __('guest.full_menu') }}</h1>
            <p class="guest-subheader__venue">{{ $shop->name }}</p>
        </div>
        <div class="guest-subheader__actions">
            @if($this->isGroupMode)
                <button type="button" wire:click="toggleGroupShare" class="guest-subheader__icon" aria-label="{{ __('guest.group_active') }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
            @else
                <button type="button" wire:click="createGroup" class="guest-subheader__icon" aria-label="{{ __('guest.group_order') }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </button>
            @endif
            <button type="button" wire:click="switchLanguage('{{ $subNextLocale }}')" class="guest-subheader__lang">{{ $locale === 'ar' ? 'EN' : 'AR' }}</button>
        </div>
    </div>
    <label class="guest-search guest-search--accent">
        <svg class="guest-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
        </svg>
        <input
            type="search"
            x-model="query"
            class="guest-search__input"
            placeholder="{{ __('guest.search_menu') }}"
            aria-label="{{ __('guest.search_menu') }}"
        >
    </label>
</section>

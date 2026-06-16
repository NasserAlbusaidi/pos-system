@php
    $guestBranding = $shop->branding ?? [];
    $guestCoverUrl = \App\Support\BrandingUrl::safe($guestBranding['cover_url'] ?? null)
        ?: asset('customer-ordering/assets/hopresso/cafe-interior.jpg');
    $guestLogoUrl = \App\Support\BrandingUrl::safe($guestBranding['logo_url'] ?? null)
        ?: asset('customer-ordering/assets/hopresso/hopresso-logo-white.png');
    $tableCopy = $tableLabel
        ? __('guest.table_context', ['table' => $tableLabel])
        : __('guest.dine_in');

    $textLanguageAttrs ??= function ($value) {
        return preg_match('/\p{Arabic}/u', (string) $value)
            ? 'lang="ar" dir="rtl"'
            : 'lang="en" dir="ltr"';
    };
@endphp

<section class="web-hero bite-hero">
    <div
        class="web-hero-bg bite-hero__bg"
        style="background-image: linear-gradient(180deg, rgba(18, 22, 15, 0.24), rgba(18, 22, 15, 0.82)), url('{{ $guestCoverUrl }}');"
        aria-hidden="true"
    ></div>

    <div class="status-bar bite-status-bar bite-status-bar--light" aria-hidden="true">
        <strong lang="en" dir="ltr">9:41</strong>
        <span class="status-icons bite-status-bar__icons" lang="en" dir="ltr">
            <svg viewBox="0 0 24 24"><path d="M4 20h2v-5H4v5Zm4 0h2v-8H8v8Zm4 0h2V9h-2v11Zm4 0h2V6h-2v14Z"/></svg>
            <svg viewBox="0 0 24 24"><path d="M3 8.8c5.8-5 12.2-5 18 0M7 13c3.3-2.6 6.7-2.6 10 0m-6 4.2a2 2 0 0 1 2 0"/></svg>
            <span class="battery"></span>
        </span>
    </div>

    <div class="web-hero-top bite-hero__top">
        <img src="{{ $guestLogoUrl }}" alt="{{ $shop->name }}" class="bite-hero__logo">
        <div class="bite-hero__actions">
            @if($this->isGroupMode)
                <button wire:click="toggleGroupShare" class="bite-icon-pill" type="button" aria-label="{{ __('guest.group_active') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2a5 5 0 0 0-9.29-2.64M7 20H2v-2a3 3 0 0 1 5.36-1.86M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                    <b>{{ __('guest.group_active') }}</b>
                </button>
            @else
                <button wire:click="createGroup" class="bite-icon-pill" type="button" aria-label="{{ __('guest.group_order') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2a5 5 0 0 0-9.29-2.64M7 20H2v-2a3 3 0 0 1 5.36-1.86M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/></svg>
                    <b>{{ __('guest.group_order') }}</b>
                </button>
            @endif

            <div class="bite-lang-switch" aria-label="{{ __('guest.language') }}">
                <button
                    wire:click="switchLanguage('{{ $locale === 'ar' ? 'en' : 'ar' }}')"
                    class="is-active"
                    type="button"
                    lang="en"
                    dir="ltr"
                >{{ $locale === 'ar' ? 'AR' : 'EN' }}</button>
            </div>
        </div>
    </div>

    <div class="web-hero-copy bite-hero__content">
        <p class="bite-hero__eyebrow"><span {!! $textLanguageAttrs($shop->name) !!}>{{ $shop->name }}</span></p>
        <h1>{{ __('guest.order_from_table') }}</h1>
        <div class="bite-hero__meta">
            {!! $prototypeIcon('location') !!}
            <span>{{ $tableCopy }}</span>
            <span>{{ __('guest.status_open') }}</span>
        </div>
    </div>
</section>

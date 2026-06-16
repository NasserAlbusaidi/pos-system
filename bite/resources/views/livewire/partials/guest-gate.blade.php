@php
    $guestBranding = $shop->branding ?? [];
    $gateLogoUrl = \App\Support\BrandingUrl::safe($guestBranding['logo_url'] ?? null)
        ?: asset('customer-ordering/assets/hopresso/hopresso-logo-white.png');
    $tableCopy = $tableLabel ? __('guest.table_label', ['table' => $tableLabel]) : __('guest.dine_in');

    $textLanguageAttrs ??= function ($value) {
        return preg_match('/\p{Arabic}/u', (string) $value)
            ? 'lang="ar" dir="rtl"'
            : 'lang="en" dir="ltr"';
    };
@endphp

<div
    class="guest-gate screen web-screen language-screen"
    data-skin="bite-language-gate"
    data-route-name="language"
    data-figma-screen="58"
    lang="en"
    dir="ltr"
    role="dialog"
    aria-modal="true"
    aria-labelledby="guest-gate-title"
    x-data="{
        focusables() {
            return [...$el.querySelectorAll('a, button, input:not([type=\'hidden\']), textarea, select, [tabindex]:not([tabindex=\'-1\'])')]
                .filter(el => ! el.hasAttribute('disabled'))
        },
        first() { return this.focusables()[0] },
        last() { return this.focusables().slice(-1)[0] },
    }"
    x-init="$nextTick(() => first()?.focus())"
    x-on:keydown.tab="
        const f = focusables();
        if (! f.length) return;
        if ($event.shiftKey && document.activeElement === first()) { $event.preventDefault(); last().focus() }
        else if (! $event.shiftKey && document.activeElement === last()) { $event.preventDefault(); first().focus() }
    "
>
    <video class="language-bg-video bite-language-gate__video" autoplay muted loop playsinline preload="auto" poster="{{ asset('customer-ordering/assets/hopresso/cafe-interior.jpg') }}" aria-hidden="true" tabindex="-1">
        <source src="{{ asset('customer-ordering/assets/hopresso/language-background.mp4') }}" type="video/mp4">
        <source src="{{ asset('customer-ordering/assets/hopresso/language-cafe-motion.webm') }}" type="video/webm">
    </video>
    <div class="language-bg-overlay bite-language-gate__shade" aria-hidden="true"></div>
    <div class="status-bar bite-status-bar bite-status-bar--light" aria-hidden="true">
        <strong lang="en" dir="ltr">9:41</strong>
        <span class="status-icons bite-status-bar__icons" lang="en" dir="ltr">
            <svg viewBox="0 0 24 24"><path d="M4 20h2v-5H4v5Zm4 0h2v-8H8v8Zm4 0h2V9h-2v11Zm4 0h2V6h-2v14Z"/></svg>
            <svg viewBox="0 0 24 24"><path d="M3 8.8c5.8-5 12.2-5 18 0M7 13c3.3-2.6 6.7-2.6 10 0m-6 4.2a2 2 0 0 1 2 0"/></svg>
            <span class="battery"></span>
        </span>
    </div>

    <section class="language-hero bite-language-gate__content">
        <img class="bite-language-gate__logo" src="{{ $gateLogoUrl }}" alt="{{ $shop->name }}">
        <div>
            <p class="bite-language-gate__venue"><span {!! $textLanguageAttrs($shop->name) !!}>{{ $shop->name }}</span> · {{ $tableCopy }}</p>
            <h1 id="guest-gate-title">Choose your language</h1>
            <span lang="ar" dir="rtl">اختر لغتك</span>
        </div>
    </section>

    <section class="language-options bite-language-gate__actions">
        <button type="button" wire:click="chooseLanguage('en')" class="bite-language-choice" lang="en" dir="ltr">
            <strong>English</strong>
            <span>Continue to menu</span>
        </button>
        <button type="button" wire:click="chooseLanguage('ar')" class="bite-language-choice" lang="ar" dir="rtl">
            <strong>العربية</strong>
            <span>المتابعة إلى القائمة</span>
        </button>
    </section>

    <div class="powered-by-bite bite-powered bite-powered--gate" aria-label="{{ __('guest.powered_by') }} Bite">
        <span>{{ __('guest.powered_by') }}</span>
        <img src="{{ asset('customer-ordering/assets/brand/bite-powered-logo.png') }}" alt="Bite">
    </div>
</div>

{{-- Language gate (screen 1) — frosted-glass language picker over the shop's
     cover image, re-skinned to the customer-ordering prototype. Full-screen
     overlay (constrained to the phone-shell column); blocks the menu until a
     language is picked. Expects: $shop. Shown only when $showLanguageGate is
     true (guarded by the caller). --}}
@php
    $gateLogoUrl = \App\Support\BrandingUrl::safe($shop->branding['logo_url'] ?? null);
    $gateCoverUrl = \App\Support\BrandingUrl::safe($shop->branding['cover_url'] ?? null);
    $gateVideoUrl = \App\Support\BrandingUrl::safe($shop->branding['gate_video_url'] ?? null);
@endphp
<div
    class="guest-gate"
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
    @if($gateCoverUrl)
        <div class="guest-gate__bg" style="background-image: url('{{ $gateCoverUrl }}')" aria-hidden="true"></div>
    @endif
    @if($gateVideoUrl)
        {{-- Optional ambient cover video. Sits over the static cover image; the
             cover is the poster, so it shows while the video buffers and remains
             the fallback if autoplay is blocked or the video fails to load. --}}
        <video
            class="guest-gate__video"
            autoplay muted loop playsinline preload="auto"
            @if($gateCoverUrl) poster="{{ $gateCoverUrl }}" @endif
            aria-hidden="true"
            tabindex="-1"
        >
            <source src="{{ $gateVideoUrl }}">
        </video>
    @endif
    <div class="guest-gate__overlay" aria-hidden="true"></div>

    <section class="guest-gate__hero">
        @if($gateLogoUrl)
            <img src="{{ $gateLogoUrl }}" alt="{{ $shop->name }}" class="guest-gate__logo">
        @endif
        <div class="guest-gate__heading">
            <p class="guest-gate__venue">{{ $shop->name }}</p>
            <h1 id="guest-gate-title">{{ __('guest.choose_language') }}</h1>
        </div>
    </section>

    <section class="guest-gate__langs">
        <button type="button" wire:click="chooseLanguage('en')" class="guest-gate__lang">
            <strong>English</strong>
            <span>{{ __('guest.continue_to_menu', [], 'en') }}</span>
        </button>
        <button type="button" wire:click="chooseLanguage('ar')" class="guest-gate__lang">
            <strong lang="ar" dir="rtl">العربية</strong>
            <span lang="ar" dir="rtl">{{ __('guest.continue_to_menu', [], 'ar') }}</span>
        </button>
    </section>

    <footer class="guest-powered">{{ __('guest.powered_by') }} <b>Bite</b></footer>
</div>

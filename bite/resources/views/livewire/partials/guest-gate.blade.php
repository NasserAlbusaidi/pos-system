{{-- Language gate (mockup screen 1) — full-screen, blocks menu until a language is picked.
     Extracted from guest-menu.blade.php to keep that file under the 800-line ceiling.
     Expects: $shop. Shown only when $showLanguageGate is true (guarded by the caller). --}}
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
    <div class="guest-gate__panel">
        <div class="guest-gate__crest">
            @php
                $gateLogoUrl = \App\Support\BrandingUrl::safe($shop->branding['logo_url'] ?? null);
            @endphp
            @if($gateLogoUrl)
                <img src="{{ $gateLogoUrl }}" alt="{{ $shop->name }}" class="guest-gate__crest-img">
            @else
                <span class="guest-gate__crest-mark">{{ Str::of($shop->name)->trim()->substr(0, 1)->upper() }}</span>
            @endif
        </div>
        <div id="guest-gate-title" class="guest-gate__word">{{ $shop->name }}</div>
        <p class="guest-gate__ask">{{ __('guest.choose_language') }}</p>
        <div class="guest-gate__langs">
            <button type="button" wire:click="chooseLanguage('en')" class="guest-gate__lang guest-gate__lang--primary">English</button>
            <button type="button" wire:click="chooseLanguage('ar')" class="guest-gate__lang guest-gate__lang--alt">العربية</button>
        </div>
    </div>
    <div class="guest-powered">{{ __('guest.powered_by') }} <b>Bite</b></div>
</div>

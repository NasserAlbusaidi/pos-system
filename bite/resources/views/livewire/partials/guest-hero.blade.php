{{-- Hero shell (mockup screens 2 / 2b) — cover, logo, name, open pill, dine-in chip.
     Extracted from guest-menu.blade.php to keep that file under the 800-line ceiling.
     Expects: $shop, $this->isGroupMode. --}}
@php
    $guestBranding = $shop->branding ?? [];
    $guestCoverUrl = \App\Support\BrandingUrl::safe($guestBranding['cover_url'] ?? null);
    $guestLogoUrl = \App\Support\BrandingUrl::safe($guestBranding['logo_url'] ?? null);
@endphp

<section class="guest-hero {{ $guestCoverUrl ? '' : 'guest-hero--placeholder' }}">
    @if($guestCoverUrl)
        <img src="{{ $guestCoverUrl }}" alt="{{ $shop->name }}" class="guest-hero__cover" loading="eager">
    @endif
    <div class="guest-hero__scrim"></div>
    <div class="guest-hero__meta">
        <div class="guest-hero__logo">
            @if($guestLogoUrl)
                <img src="{{ $guestLogoUrl }}" alt="{{ $shop->name }}" class="guest-hero__logo-img">
            @else
                <span class="guest-hero__wordmark">{{ Str::of($shop->name)->trim()->substr(0, 1)->upper() }}</span>
            @endif
        </div>
        <h2 class="guest-hero__name">{{ $shop->name }}</h2>
        <div class="guest-hero__sub">
            <span class="guest-hero__pill">
                <span class="guest-hero__dot"></span>{{ __('guest.status_open') }}
            </span>
            <span class="guest-hero__chip">{{ __('guest.dine_in') }}</span>
        </div>
    </div>
</section>

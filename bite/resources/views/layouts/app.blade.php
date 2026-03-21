<!DOCTYPE html>
@php
    $theme = 'warm';
    if (isset($shop)) {
        $branding = $shop->branding ?? [];
        $theme = in_array($branding['theme'] ?? '', ['warm', 'modern', 'dark'])
            ? $branding['theme']
            : 'warm';
    }
@endphp
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $direction ?? 'ltr' }}" data-theme="{{ $theme }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <meta name="theme-color" content="#EC6D2E">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @if(isset($shop))
            @php
                $branding = $shop->branding ?? [];
                $paperHex = $branding['paper'] ?? '#FDFCF8';
                $inkHex = $branding['ink'] ?? '#1A1918';
                $cremaHex = $branding['accent'] ?? '#CC5500';

                $toRgb = function ($hex) {
                    $hex = ltrim((string) $hex, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                    }
                    if (strlen($hex) !== 6) {
                        return '0 0 0';
                    }
                    [$r, $g, $b] = sscanf($hex, '%02x%02x%02x');
                    return "{$r} {$g} {$b}";
                };

                $parseHexToArr = function ($hex) {
                    $hex = ltrim((string) $hex, '#');
                    if (strlen($hex) === 3) {
                        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                    }
                    if (strlen($hex) !== 6) {
                        return [0, 0, 0];
                    }
                    return array_values(sscanf($hex, '%02x%02x%02x'));
                };

                $mix = function (array $a, array $b, float $t): array {
                    return [
                        (int) round($a[0] + ($b[0] - $a[0]) * $t),
                        (int) round($a[1] + ($b[1] - $a[1]) * $t),
                        (int) round($a[2] + ($b[2] - $a[2]) * $t),
                    ];
                };

                $toRgbStr = fn(array $c): string => "{$c[0]} {$c[1]} {$c[2]}";

                $paper = $parseHexToArr($paperHex);
                $ink   = $parseHexToArr($inkHex);

                // Derived tokens — linear RGB interpolation
                $canvas     = $mix($paper, $ink, 0.06);           // barely darker than paper
                $panel      = $mix($paper, [255, 255, 255], 0.30); // slightly lighter than paper
                $panelMuted = $mix($paper, $ink, 0.12);           // skeleton shimmer bg
                $line       = $mix($paper, $ink, 0.18);           // warm border color
                $inkSoft    = $mix($ink, $paper, 0.55);           // secondary text
            @endphp
            <style>
                :root {
                    --paper: {{ $toRgb($paperHex) }};
                    --ink: {{ $toRgb($inkHex) }};
                    --crema: {{ $toRgb($cremaHex) }};
                    --canvas: {{ $toRgbStr($canvas) }};
                    --panel: {{ $toRgbStr($panel) }};
                    --panel-muted: {{ $toRgbStr($panelMuted) }};
                    --line: {{ $toRgbStr($line) }};
                    --ink-soft: {{ $toRgbStr($inkSoft) }};
                }
            </style>
        @endif
    </head>
    <body class="min-h-full font-sans text-ink antialiased">
        <x-toast />

        @if(session('impersonator_id'))
            <div class="sticky top-0 z-[100] border-b border-alert/40 bg-alert text-panel">
                <div class="mx-auto flex max-w-7xl items-center justify-center gap-4 px-4 py-2 font-mono text-[11px] font-bold uppercase tracking-[0.2em]">
                    <span>Admin Access: Viewing as User</span>
                    <a href="{{ route('impersonation.leave') }}" class="rounded-md border border-panel/30 px-2 py-1 hover:bg-panel/10">Exit</a>
                </div>
            </div>
        @endif

        <div class="min-h-screen overflow-x-hidden">
            @auth
                <livewire:layout.navigation />
            @endauth

            @if (isset($header))
                <header class="relative z-10 border-b border-line/70 bg-panel/75 backdrop-blur-xl">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <main>
                {{ $slot }}
            </main>
        </div>

        <x-confirm-modal />

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    </body>
</html>

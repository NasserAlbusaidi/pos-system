<!DOCTYPE html>
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $direction ?? 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <meta name="theme-color" content="#EC6D2E">
        <meta name="apple-mobile-web-app-capable" content="yes">
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
            @endphp
            <style>
                :root {
                    --paper: {{ $toRgb($paperHex) }};
                    --ink: {{ $toRgb($inkHex) }};
                    --crema: {{ $toRgb($cremaHex) }};
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

        <div class="min-h-screen">
            @auth
                <livewire:layout.navigation />
            @endauth

            @if (isset($header))
                <header class="border-b border-line/70 bg-panel/75 backdrop-blur-xl">
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

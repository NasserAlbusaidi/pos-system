<!DOCTYPE html>
<html lang="{{ $currentLocale ?? 'en' }}" dir="{{ $direction ?? 'ltr' }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <meta name="theme-color" content="#EC6D2E">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <title>{{ $title ?? 'Bite' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @php $shop = $shop ?? Illuminate\Support\Facades\Auth::user()?->shop; @endphp
        @if($shop)
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
    <body class="h-full font-sans text-ink">
        {{-- Navigation progress bar for wire:navigate --}}
        <div
            x-data="{ navigating: false }"
            x-on:livewire:navigate-start.window="navigating = true"
            x-on:livewire:navigate-end.window="navigating = false"
            x-show="navigating"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="nav-progress"
            style="display: none;"
        ></div>

        <x-toast />

        <div class="flex h-full flex-col overflow-hidden md:flex-row">
            <livewire:layout.admin-navigation />

            <div class="relative flex min-h-0 min-w-0 flex-1 flex-col">
                <header class="border-b border-line/80 bg-panel/80 backdrop-blur-xl">
                    <div class="mx-auto flex h-16 w-full max-w-[1600px] items-center justify-between px-4 sm:px-6 xl:px-8">
                        <div class="fade-rise">
                            <p class="section-headline">Bite Operations</p>
                            <h1 class="font-display text-xl font-extrabold leading-none text-ink">{{ $header ?? 'Overview' }}</h1>
                        </div>

                        <div class="flex items-center gap-2 sm:gap-3">
                            <span class="tag hidden sm:inline-flex">Live Data</span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-line bg-panel px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">
                                <span class="status-dot status-live"></span>
                                {{ now()->format('D, M j H:i') }}
                            </span>
                        </div>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto overflow-x-hidden">
                    <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 xl:p-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <x-confirm-modal />

        @stack('scripts')
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    </body>
</html>

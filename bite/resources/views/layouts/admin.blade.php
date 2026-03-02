<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#EC692E">
        <title>{{ $title ?? 'Bite' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @php
            $shop = Illuminate\Support\Facades\Auth::user()?->shop;
        @endphp
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
        <div class="flex h-full overflow-hidden">
            <livewire:layout.admin-navigation />

            <div class="relative flex min-w-0 flex-1 flex-col">
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

        @stack('scripts')
        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ $currentLocale ?? 'en' }}" dir="{{ $direction ?? 'ltr' }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <meta name="theme-color" content="#004225">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <title>{{ $title ?? 'Bite' }}</title>

        {{-- Bite renders in its fixed green identity (app.css :root) with Bai Jamjuree
             (self-hosted in app.css; Arabic stays IBM Plex). Per-shop branding is scoped
             to the customer-facing guest menu only. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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

            <div id="admin-content" class="relative flex min-h-0 min-w-0 flex-1 flex-col">
                {{-- Pages can opt out of this default chrome with <x-slot:chromeless>
                     and render their own full-bleed header/content (e.g. the
                     operations dashboard, whose header carries Livewire controls). --}}
                @unless(isset($chromeless))
                    <header class="sticky top-0 z-40 border-b border-line bg-cream/85 backdrop-blur-xl">
                        <div class="mx-auto flex h-16 w-full max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6 xl:px-8">
                            <div class="fade-rise">
                                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-[color:var(--bite-green)]">Bite Operations</p>
                                <h1 class="mt-0.5 font-display text-xl font-bold leading-none text-forest">{{ $header ?? 'Overview' }}</h1>
                            </div>

                            <div class="flex items-center gap-2 sm:gap-3">
                                <span class="hidden items-center gap-2 rounded-full border px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[0.14em] sm:inline-flex"
                                      style="border-color: color-mix(in srgb, var(--bite-green) 35%, transparent); background: var(--bite-lime-100); color: var(--bite-pine);">
                                    <span class="h-1.5 w-1.5 rounded-full" style="background: var(--bite-green); animation: pulseDot 1.8s ease-in-out infinite;"></span>
                                    Live Data
                                </span>
                                <span class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-soft">
                                    {{ now()->format('D, M j · H:i') }}
                                </span>
                            </div>
                        </div>
                    </header>
                @endunless

                <main class="flex-1 overflow-y-auto overflow-x-hidden">
                    @isset($chromeless)
                        {{ $slot }}
                    @else
                        <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 xl:p-8">
                            {{ $slot }}
                        </div>
                    @endisset
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

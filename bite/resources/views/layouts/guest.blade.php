<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#EC692E">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans text-ink antialiased">
        <div class="relative flex min-h-screen items-center justify-center px-4 py-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_12%_8%,rgba(236,105,46,0.16),transparent_36%),radial-gradient(circle_at_82%_18%,rgba(33,138,111,0.12),transparent_34%)]"></div>

            <div class="w-full max-w-md fade-rise">
                <div class="mb-6 flex items-center justify-center">
                    <a href="/" wire:navigate class="inline-flex items-center gap-3 rounded-xl border border-line bg-panel px-4 py-3 shadow-[0_16px_28px_-22px_rgba(10,15,24,0.9)]">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-line bg-ink text-panel font-display text-xl font-black">B</span>
                        <span class="font-display text-2xl font-extrabold tracking-tight">{{ config('app.name', 'Bite') }}</span>
                    </a>
                </div>

                <div class="surface-card px-6 py-7 shadow-[0_26px_42px_-30px_rgba(8,13,23,0.95)] sm:px-8 sm:py-9">
                    {{ $slot }}
                </div>
            </div>
        </div>

        <script>
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js');
            }
        </script>
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="192x192" href="/icons/icon-192x192.png">
        <meta name="theme-color" content="#EC6D2E">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <title>Bite Platform Control</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-ink">
        <div class="flex h-full overflow-hidden">
            <aside class="hidden h-full w-72 flex-col border-r border-panel/10 bg-ink text-panel md:flex">
                <div class="border-b border-panel/10 p-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-panel/20 bg-panel/10">
                            <span class="font-display text-xl font-black text-panel">S</span>
                        </div>
                        <div>
                            <p class="font-display text-2xl font-extrabold leading-none">Super Admin</p>
                            <p class="mt-1 font-mono text-[10px] font-semibold uppercase tracking-[0.2em] text-panel/55">Platform Control</p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-2 overflow-y-auto p-4">
                    <a href="{{ route('super-admin.dashboard') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.18em] transition-all duration-200 {{ request()->routeIs('super-admin.dashboard') ? 'border-crema/60 bg-crema text-panel' : 'border-transparent text-panel/70 hover:border-panel/20 hover:bg-panel/10 hover:text-panel' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-panel/15 bg-panel/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </span>
                        <span>Overview</span>
                    </a>

                    <a href="{{ route('super-admin.shops.index') }}" class="group flex items-center gap-3 rounded-xl border px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.18em] transition-all duration-200 {{ request()->routeIs('super-admin.shops.*') ? 'border-crema/60 bg-crema text-panel' : 'border-transparent text-panel/70 hover:border-panel/20 hover:bg-panel/10 hover:text-panel' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-panel/15 bg-panel/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 01-2-2h-4v4m6-4v4m-6-4v4" /></svg>
                        </span>
                        <span>Shops</span>
                    </a>

                    <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 rounded-xl border border-transparent px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-panel/70 transition-all duration-200 hover:border-panel/20 hover:bg-panel/10 hover:text-panel">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-panel/15 bg-panel/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </span>
                        <span>Shop View</span>
                    </a>
                </nav>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <header class="border-b border-line/70 bg-panel/80 backdrop-blur-xl">
                    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div>
                            <p class="section-headline">Bite Platform</p>
                            <h1 class="font-display text-xl font-extrabold leading-none text-ink">{{ $header ?? 'Super Admin' }}</h1>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-signal/30 bg-signal/10 px-3 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-signal">
                            <span class="status-dot status-live"></span>
                            System Online
                        </span>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto w-full max-w-7xl">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <x-confirm-modal />
    </body>
</html>

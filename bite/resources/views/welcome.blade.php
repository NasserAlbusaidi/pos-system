<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Bite POS</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full overflow-x-hidden font-sans text-ink antialiased">
        <main class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-center px-4 py-10 sm:px-6">
            <div class="pointer-events-none absolute -left-24 top-0 h-56 w-56 rounded-full bg-crema/25 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-28 bottom-4 h-60 w-60 rounded-full bg-signal/20 blur-3xl"></div>

            <section class="surface-card relative overflow-hidden p-6 sm:p-10">
                <div class="grid gap-8 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div class="space-y-5">
                        <span class="tag">Bite POS Platform</span>
                        <div>
                            <h1 class="font-display text-4xl font-extrabold leading-[0.95] text-ink sm:text-6xl">Hospitality Operations, Productized.</h1>
                            <p class="mt-4 max-w-xl text-base leading-relaxed text-ink-soft">Run counter, kitchen, inventory, and reporting from a single interface that feels designed for real service teams.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn-primary w-full justify-center" wire:navigate>
                                        Go to Dashboard
                                    </a>
                                    <a href="{{ url('/admin') }}" class="btn-secondary w-full justify-center" wire:navigate>
                                        Platform Admin
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn-primary w-full justify-center" wire:navigate>
                                        Staff Login
                                    </a>

                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn-secondary w-full justify-center" wire:navigate>
                                            Create Shop
                                        </a>
                                    @endif
                                @endauth
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <article class="rounded-xl border border-line bg-panel p-4">
                            <p class="section-headline">Realtime Service Layer</p>
                            <p class="mt-2 text-sm text-ink">Live ticket flow between front counter and kitchen display with status synchronization.</p>
                        </article>
                        <article class="rounded-xl border border-line bg-panel p-4">
                            <p class="section-headline">Operational Controls</p>
                            <p class="mt-2 text-sm text-ink">Manager tools for reports, inventory, menu composition, and secure staff workflows.</p>
                        </article>
                        <article class="rounded-xl border border-line bg-panel p-4">
                            <p class="section-headline">Guest Experience</p>
                            <p class="mt-2 text-sm text-ink">Mobile guest ordering and tokenized tracking for privacy-safe order follow-up.</p>
                        </article>
                    </div>
                </div>
            </section>

            <footer class="mt-6 flex flex-col items-start justify-between gap-2 border-t border-line/70 pt-4 sm:flex-row sm:items-center">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">Bite POS Product Interface</p>
                <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-soft">{{ now()->year }} Bite Systems</p>
            </footer>
        </main>
    </body>
</html>

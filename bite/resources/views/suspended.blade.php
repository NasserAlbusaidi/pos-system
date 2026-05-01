<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Suspended - {{ config('app.name', 'Bite POS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-body text-ink antialiased">
    <main class="flex min-h-screen items-center justify-center px-6 py-12">
        <section class="w-full max-w-md rounded-lg border border-line bg-panel px-6 py-8 text-center shadow-sm">
            <p class="font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-ink-soft">
                {{ config('app.name', 'Bite POS') }}
            </p>
            <h1 class="mt-4 text-2xl font-bold text-ink">This account has been suspended.</h1>
            <p class="mt-3 text-sm leading-6 text-ink-soft">Contact support.</p>
        </section>
    </main>
</body>
</html>

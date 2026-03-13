<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        $direction = $locale === 'ar' ? 'rtl' : 'ltr';
        View::share('direction', $direction);
        View::share('currentLocale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        // Super admin routes: always English
        if ($request->routeIs('super-admin.*')) {
            return 'en';
        }

        // Guest routes: session > shop branding > 'en'
        if ($request->routeIs('guest.*')) {
            $locale = session('guest_locale');
            if ($this->isValid($locale)) {
                return $locale;
            }

            $shop = $request->route('shop');
            if ($shop) {
                $branding = $shop->branding ?? [];
                $locale = $branding['language'] ?? null;
                if ($this->isValid($locale)) {
                    return $locale;
                }
            }

            return 'en';
        }

        // Authenticated admin routes: session > shop branding > 'en'
        $user = $request->user();
        if ($user) {
            $locale = session('admin_locale');
            if ($this->isValid($locale)) {
                return $locale;
            }

            $shop = $user->shop;
            if ($shop) {
                $branding = $shop->branding ?? [];
                $locale = $branding['language'] ?? null;
                if ($this->isValid($locale)) {
                    return $locale;
                }
            }
        }

        return 'en';
    }

    private function isValid(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::SUPPORTED_LOCALES);
    }
}

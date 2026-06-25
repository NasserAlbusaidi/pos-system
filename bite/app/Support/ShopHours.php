<?php

namespace App\Support;

use App\Models\Shop;
use Carbon\CarbonInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Throwable;

class ShopHours
{
    public static function isOpen(Shop $shop, ?CarbonInterface $at = null): bool
    {
        $branding = $shop->branding ?? [];
        $hours = $branding['business_hours'] ?? null;

        if (! is_array($hours) || $hours === []) {
            return true;
        }

        $timezone = self::safeTimezone($branding['timezone'] ?? null);
        $now = ($at ? Carbon::instance($at) : now())->copy()->timezone($timezone);
        $minutes = self::minutesSinceMidnight($now->format('H:i'));

        $today = strtolower($now->format('l'));
        if (self::entryIsOpen($hours[$today] ?? null, $minutes, false)) {
            return true;
        }

        $yesterday = strtolower($now->copy()->subDay()->format('l'));

        return self::entryIsOpen($hours[$yesterday] ?? null, $minutes, true);
    }

    private static function entryIsOpen(mixed $entry, int $currentMinutes, bool $fromPreviousDay): bool
    {
        if (! is_array($entry)) {
            return ! $fromPreviousDay;
        }

        if ((bool) ($entry['closed'] ?? false)) {
            return false;
        }

        $open = self::minutesSinceMidnight((string) ($entry['open'] ?? ''));
        $close = self::minutesSinceMidnight((string) ($entry['close'] ?? ''));

        if ($open === null || $close === null) {
            return ! $fromPreviousDay;
        }

        if ($open === $close) {
            return true;
        }

        if ($open < $close) {
            return ! $fromPreviousDay
                && $currentMinutes >= $open
                && $currentMinutes < $close;
        }

        return $fromPreviousDay
            ? $currentMinutes < $close
            : $currentMinutes >= $open;
    }

    private static function minutesSinceMidnight(?string $time): ?int
    {
        if (! is_string($time) || ! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    private static function safeTimezone(mixed $timezone): string
    {
        foreach ([$timezone, config('app.timezone'), 'UTC'] as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            try {
                new DateTimeZone($candidate);

                return $candidate;
            } catch (Throwable) {
                continue;
            }
        }

        return 'UTC';
    }
}

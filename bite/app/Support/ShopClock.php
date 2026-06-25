<?php

namespace App\Support;

use App\Models\Shop;
use Carbon\CarbonInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;
use Throwable;

class ShopClock
{
    public static function timezone(Shop $shop): string
    {
        return self::safeTimezone(($shop->branding ?? [])['timezone'] ?? null);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function currentLocalDayUtcRange(Shop $shop, ?CarbonInterface $at = null): array
    {
        return self::localDayUtcRange($shop, self::localDate($shop, $at));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function localDayUtcRange(Shop $shop, string $localDate): array
    {
        $localDay = Carbon::parse($localDate, self::timezone($shop));

        return [
            $localDay->copy()->startOfDay()->timezone('UTC'),
            $localDay->copy()->endOfDay()->timezone('UTC'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function recentLocalDaysUtcRange(Shop $shop, int $days, ?CarbonInterface $at = null): array
    {
        $days = max(1, $days);
        $localEnd = self::localNow($shop, $at)->endOfDay();
        $localStart = $localEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            $localStart->timezone('UTC'),
            $localEnd->timezone('UTC'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function recentLocalDates(Shop $shop, int $days, ?CarbonInterface $at = null): array
    {
        $days = max(1, $days);
        $localNow = self::localNow($shop, $at);

        return collect(range($days - 1, 0))
            ->map(fn (int $offset): string => $localNow->copy()->subDays($offset)->toDateString())
            ->values()
            ->all();
    }

    public static function localDate(Shop $shop, ?CarbonInterface $at = null, int $offsetDays = 0): string
    {
        return self::localNow($shop, $at)->addDays($offsetDays)->toDateString();
    }

    public static function localHour(Shop $shop, CarbonInterface $at): string
    {
        return Carbon::instance($at)
            ->copy()
            ->timezone(self::timezone($shop))
            ->format('H');
    }

    public static function localWeekdayIndex(Shop $shop, CarbonInterface $at): int
    {
        return (int) Carbon::instance($at)
            ->copy()
            ->timezone(self::timezone($shop))
            ->format('w');
    }

    private static function localNow(Shop $shop, ?CarbonInterface $at = null): Carbon
    {
        return ($at ? Carbon::instance($at) : now())
            ->copy()
            ->timezone(self::timezone($shop));
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

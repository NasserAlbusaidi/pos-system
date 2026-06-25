<?php

namespace App\Support;

use Illuminate\Support\Collection;

class HourlyBuckets
{
    public static function counts(array $rawCounts, bool $withClockSuffix = false): Collection
    {
        $normalized = [];

        foreach ($rawCounts as $hour => $count) {
            $hour = self::normalizeHourKey($hour);
            if ($hour === null) {
                continue;
            }

            $normalized[$hour] = (int) $count;
        }

        return collect(range(0, 23))
            ->map(function (int $hour) use ($normalized, $withClockSuffix): array {
                $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);

                return [
                    'hour' => $withClockSuffix ? $key.':00' : $key,
                    'count' => $normalized[$key] ?? 0,
                ];
            })
            ->values();
    }

    private static function normalizeHourKey(int|string $hour): ?string
    {
        if (is_int($hour)) {
            return $hour >= 0 && $hour <= 23
                ? str_pad((string) $hour, 2, '0', STR_PAD_LEFT)
                : null;
        }

        $hour = trim($hour);
        if (! preg_match('/^\d{1,2}/', $hour, $matches)) {
            return null;
        }

        $hourNumber = (int) $matches[0];

        return $hourNumber >= 0 && $hourNumber <= 23
            ? str_pad((string) $hourNumber, 2, '0', STR_PAD_LEFT)
            : null;
    }
}

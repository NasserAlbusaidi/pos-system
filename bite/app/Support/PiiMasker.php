<?php

namespace App\Support;

class PiiMasker
{
    public static function phone(string $phone): string
    {
        if (strlen($phone) < 8) {
            return str_repeat('*', strlen($phone));
        }

        $last4 = substr($phone, -4);
        $prefix = substr($phone, 0, 4);
        $middleLen = strlen($phone) - strlen($prefix) - 4;

        return $prefix.str_repeat('*', max($middleLen, 0)).$last4;
    }

    public static function email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }

        $firstChar = mb_substr($parts[0], 0, 1);

        return $firstChar.'***@'.$parts[1];
    }

    public static function ip(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0].'.'.$parts[1].'.***';
        }

        return '***';
    }
}

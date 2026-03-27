<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class PiiMaskingProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['phone']) && is_string($context['phone'])) {
            $context['phone'] = $this->maskPhone($context['phone']);
        }
        if (isset($context['email']) && is_string($context['email'])) {
            $context['email'] = $this->maskEmail($context['email']);
        }
        if (isset($context['ip']) && is_string($context['ip'])) {
            $context['ip'] = $this->maskIp($context['ip']);
        }

        return $record->with(context: $context);
    }

    private function maskPhone(string $phone): string
    {
        // Keep country code prefix and last 4 digits: +968****4567
        if (strlen($phone) < 8) {
            return str_repeat('*', strlen($phone));
        }
        $last4 = substr($phone, -4);
        $prefix = substr($phone, 0, 4); // e.g., +968
        $middleLen = strlen($phone) - strlen($prefix) - 4;

        return $prefix . str_repeat('*', max($middleLen, 0)) . $last4;
    }

    private function maskEmail(string $email): string
    {
        // Keep first char + domain: n***@bite.com
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***';
        }
        $local = $parts[0];
        $firstChar = mb_substr($local, 0, 1);

        return $firstChar . '***@' . $parts[1];
    }

    private function maskIp(string $ip): string
    {
        // Mask last two octets: 192.168.***
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.***';
        }
        // IPv6 or unexpected format — mask aggressively
        return '***';
    }
}

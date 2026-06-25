<?php

namespace Tests\Unit;

use App\Support\HourlyBuckets;
use PHPUnit\Framework\TestCase;

class HourlyBucketsTest extends TestCase
{
    public function test_counts_accept_numeric_and_padded_hour_keys(): void
    {
        $buckets = HourlyBuckets::counts([
            8 => 3,
            '09' => 2,
            '17' => 1,
        ]);

        $this->assertSame(['hour' => '08', 'count' => 3], $buckets[8]);
        $this->assertSame(['hour' => '09', 'count' => 2], $buckets[9]);
        $this->assertSame(['hour' => '17', 'count' => 1], $buckets[17]);
        $this->assertSame(['hour' => '18', 'count' => 0], $buckets[18]);
    }

    public function test_counts_can_format_shift_report_labels(): void
    {
        $buckets = HourlyBuckets::counts([8 => 3], withClockSuffix: true);

        $this->assertSame(['hour' => '08:00', 'count' => 3], $buckets[8]);
    }
}

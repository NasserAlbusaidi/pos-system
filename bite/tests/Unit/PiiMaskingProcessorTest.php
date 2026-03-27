<?php

namespace Tests\Unit;

use App\Logging\PiiMaskingProcessor;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class PiiMaskingProcessorTest extends TestCase
{
    private PiiMaskingProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new PiiMaskingProcessor();
    }

    private function makeRecord(array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: $context,
        );
    }

    public function test_masks_phone_number(): void
    {
        $record = $this->makeRecord(['phone' => '+96891234567']);
        $result = ($this->processor)($record);

        $this->assertSame('+968****4567', $result->context['phone']);
    }

    public function test_masks_different_phone_number(): void
    {
        $record = $this->makeRecord(['phone' => '+96899887766']);
        $result = ($this->processor)($record);

        $this->assertSame('+968****7766', $result->context['phone']);
    }

    public function test_masks_email_address(): void
    {
        $record = $this->makeRecord(['email' => 'nasser@bite.com']);
        $result = ($this->processor)($record);

        $this->assertSame('n***@bite.com', $result->context['email']);
    }

    public function test_masks_different_email_address(): void
    {
        $record = $this->makeRecord(['email' => 'admin@example.org']);
        $result = ($this->processor)($record);

        $this->assertSame('a***@example.org', $result->context['email']);
    }

    public function test_masks_ip_address(): void
    {
        $record = $this->makeRecord(['ip' => '192.168.1.100']);
        $result = ($this->processor)($record);

        $this->assertSame('192.168.***', $result->context['ip']);
    }

    public function test_masks_different_ip_address(): void
    {
        $record = $this->makeRecord(['ip' => '10.0.0.1']);
        $result = ($this->processor)($record);

        $this->assertSame('10.0.***', $result->context['ip']);
    }

    public function test_leaves_context_unchanged_when_no_pii_fields(): void
    {
        $context = ['user_id' => 123, 'action' => 'login', 'shop_id' => 5];
        $record = $this->makeRecord($context);
        $result = ($this->processor)($record);

        $this->assertSame($context, $result->context);
    }

    public function test_handles_empty_context(): void
    {
        $record = $this->makeRecord([]);
        $result = ($this->processor)($record);

        $this->assertSame([], $result->context);
    }

    public function test_handles_null_phone_value(): void
    {
        // null values should not cause errors — only string values are masked
        $record = $this->makeRecord(['phone' => null]);
        $result = ($this->processor)($record);

        $this->assertNull($result->context['phone']);
    }

    public function test_handles_null_email_value(): void
    {
        $record = $this->makeRecord(['email' => null]);
        $result = ($this->processor)($record);

        $this->assertNull($result->context['email']);
    }

    public function test_handles_null_ip_value(): void
    {
        $record = $this->makeRecord(['ip' => null]);
        $result = ($this->processor)($record);

        $this->assertNull($result->context['ip']);
    }

    public function test_masks_all_pii_fields_simultaneously(): void
    {
        $record = $this->makeRecord([
            'phone' => '+96891234567',
            'email' => 'nasser@bite.com',
            'ip' => '192.168.1.100',
            'user_id' => 42,
        ]);
        $result = ($this->processor)($record);

        $this->assertSame('+968****4567', $result->context['phone']);
        $this->assertSame('n***@bite.com', $result->context['email']);
        $this->assertSame('192.168.***', $result->context['ip']);
        $this->assertSame(42, $result->context['user_id']);
    }

    public function test_returns_immutable_log_record(): void
    {
        $record = $this->makeRecord(['phone' => '+96891234567']);
        $result = ($this->processor)($record);

        // The original record should not be mutated
        $this->assertSame('+96891234567', $record->context['phone']);
        $this->assertSame('+968****4567', $result->context['phone']);
        $this->assertNotSame($record, $result);
    }
}

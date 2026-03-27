<?php

namespace Tests\Feature;

use Monolog\Formatter\GoogleCloudLoggingFormatter;
use Tests\TestCase;

class StructuredLoggingTest extends TestCase
{
    public function test_stackdriver_channel_exists_in_config(): void
    {
        $channel = config('logging.channels.stackdriver');

        $this->assertNotNull($channel, 'The stackdriver log channel must be defined in config/logging.php');
    }

    public function test_stackdriver_channel_uses_google_cloud_logging_formatter(): void
    {
        $formatter = config('logging.channels.stackdriver.formatter');

        $this->assertSame(
            GoogleCloudLoggingFormatter::class,
            $formatter,
            'The stackdriver channel must use GoogleCloudLoggingFormatter',
        );
    }

    public function test_stackdriver_channel_includes_pii_masking_processor(): void
    {
        $processors = config('logging.channels.stackdriver.processors');

        $this->assertIsArray($processors);
        $this->assertContains(
            \App\Logging\PiiMaskingProcessor::class,
            $processors,
            'The stackdriver channel must include PiiMaskingProcessor',
        );
    }

    public function test_google_cloud_logging_formatter_class_exists(): void
    {
        $this->assertTrue(
            class_exists(GoogleCloudLoggingFormatter::class),
            'GoogleCloudLoggingFormatter must exist in the installed Monolog version',
        );
    }

    public function test_stackdriver_channel_streams_to_stderr(): void
    {
        $handlerWith = config('logging.channels.stackdriver.handler_with');

        $this->assertIsArray($handlerWith);
        $this->assertSame('php://stderr', $handlerWith['stream']);
    }

    public function test_sentry_traces_sample_rate_defaults_to_ten_percent(): void
    {
        // D-13: Sentry must default to 10% performance trace sampling in production
        // without requiring SENTRY_TRACES_SAMPLE_RATE to be explicitly set.
        // In the test environment this env var is not set, so the default of 0.10 applies.
        $rate = config('sentry.traces_sample_rate');

        $this->assertSame(0.10, $rate, 'Sentry traces_sample_rate must default to 0.10 (10%) per D-13');
    }
}

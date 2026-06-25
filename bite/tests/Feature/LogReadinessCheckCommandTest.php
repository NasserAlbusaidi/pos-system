<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class LogReadinessCheckCommandTest extends TestCase
{
    private string $logDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDirectory = storage_path('framework/testing/log-readiness-'.Str::uuid());
        File::ensureDirectoryExists($this->logDirectory);
        Carbon::setTestNow(Carbon::parse('2026-06-25 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        File::deleteDirectory($this->logDirectory);

        parent::tearDown();
    }

    public function test_log_check_passes_when_no_recent_application_errors_exist(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:45:00] testing.INFO: Order #1001 paid in cash
[2026-06-25 11:50:00] testing.WARNING: Slow request detected
[2026-06-25 10:30:00] local.ERROR: Old webhook retry failure outside the handoff window
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
        ])->assertSuccessful()
            ->expectsOutputToContain('PASS No ERROR, CRITICAL, ALERT, or EMERGENCY entries found in the last 60 minute(s) for testing.');
    }

    public function test_log_check_fails_when_recent_application_error_exists(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:15:00] testing.INFO: Kitchen display polled
[2026-06-25 11:20:00] testing.ERROR: Payment reversal failed for order 2002
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
        ])->assertFailed()
            ->expectsOutputToContain('FAIL '.$path.':2 testing.ERROR Payment reversal failed for order 2002')
            ->expectsOutputToContain('1 recent application error(s) found.');
    }

    public function test_log_check_json_reports_recent_application_errors(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:30:00] testing.CRITICAL: Shift close total mismatch
LOG);

        $exitCode = Artisan::call('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
            '--json' => true,
        ]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(1, $exitCode);
        $this->assertFalse($payload['ok']);
        $this->assertSame(60, $payload['minutes']);
        $this->assertSame('testing', $payload['environment']);
        $this->assertFalse($payload['include_all_environments']);
        $this->assertSame([$path], $payload['files']);
        $this->assertSame(1, $payload['match_count']);
        $this->assertFalse($payload['truncated']);
        $this->assertSame('testing', $payload['matches'][0]['environment']);
        $this->assertSame('CRITICAL', $payload['matches'][0]['level']);
        $this->assertSame('Shift close total mismatch', $payload['matches'][0]['message']);
    }

    public function test_log_check_caps_printed_matches_but_reports_total_count(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:10:00] testing.ERROR: First recent failure
[2026-06-25 11:20:00] testing.ERROR: Second recent failure
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--limit' => 1,
            '--path' => [$path],
        ])->assertFailed()
            ->expectsOutputToContain('FAIL '.$path.':1 testing.ERROR First recent failure')
            ->doesntExpectOutputToContain('Second recent failure')
            ->expectsOutputToContain('FAIL 1 more recent application error(s) hidden by --limit=1.')
            ->expectsOutputToContain('2 recent application error(s) found.');
    }

    public function test_log_check_ignores_non_current_environment_errors_by_default(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:20:00] local.ERROR: Prior local smoke command failed
[2026-06-25 11:21:00] production.CRITICAL: Prior production incident copied into the file
[2026-06-25 11:22:00] testing.INFO: Current test environment stayed clean
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
        ])->assertSuccessful()
            ->doesntExpectOutputToContain('Prior local smoke command failed')
            ->doesntExpectOutputToContain('Prior production incident copied into the file')
            ->expectsOutputToContain('for testing.');
    }

    public function test_log_check_can_scan_all_environments_when_requested(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:20:00] local.ERROR: Prior local smoke command failed
[2026-06-25 11:22:00] testing.INFO: Current test environment stayed clean
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
            '--include-all-environments' => true,
        ])->assertFailed()
            ->expectsOutputToContain('FAIL '.$path.':1 local.ERROR Prior local smoke command failed');
    }

    public function test_log_check_can_target_an_explicit_environment(): void
    {
        $path = $this->writeLog(<<<'LOG'
[2026-06-25 11:20:00] local.ERROR: Prior local smoke command failed
[2026-06-25 11:30:00] production.CRITICAL: Shift close total mismatch
[2026-06-25 11:40:00] testing.ERROR: Test-only fixture should be ignored
LOG);

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
            '--environment' => 'production',
        ])->assertFailed()
            ->expectsOutputToContain('FAIL '.$path.':2 production.CRITICAL Shift close total mismatch')
            ->doesntExpectOutputToContain('Test-only fixture should be ignored');
    }

    public function test_log_check_fails_for_unreadable_explicit_path(): void
    {
        $path = $this->logDirectory.'/missing.log';

        $this->artisan('bite:log-check', [
            '--minutes' => 60,
            '--path' => [$path],
        ])->assertFailed()
            ->expectsOutputToContain('FAIL '.$path.' is not a readable log file');
    }

    private function writeLog(string $contents): string
    {
        $path = $this->logDirectory.'/laravel.log';
        File::put($path, trim($contents).PHP_EOL);

        return $path;
    }
}

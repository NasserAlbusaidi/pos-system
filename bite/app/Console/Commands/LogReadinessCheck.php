<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class LogReadinessCheck extends Command
{
    private const BLOCKING_LEVELS = ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'];

    private const LOG_LINE_PATTERN = '/^\[(?<timestamp>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<channel>[^.]+)\.(?<level>EMERGENCY|ALERT|CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG):\s*(?<message>.*)$/';

    protected $signature = 'bite:log-check
        {--minutes=60 : Look back this many minutes}
        {--limit=25 : Maximum number of matching log entries to print}
        {--path=* : Log file path to inspect. Defaults to storage/logs/*.log}
        {--environment= : Only count log entries for this Laravel environment. Defaults to current APP_ENV}
        {--include-all-environments : Scan all Laravel environment log channels}
        {--json : Output machine-readable JSON}';

    protected $description = 'Check recent Laravel logs for application errors before a restaurant handoff.';

    public function handle(): int
    {
        $minutes = $this->minutesOption();
        $limit = $this->limitOption();
        $environment = $this->environmentOption();

        if ($minutes === null || $limit === null || $environment === '') {
            $this->error('The --minutes and --limit options must be positive integers, and --environment must not be empty.');

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subMinutes($minutes);
        $paths = $this->logPaths();
        $unreadable = $this->unreadablePaths($paths);
        $readablePaths = array_values(array_diff($paths, $unreadable));
        $matches = $this->recentErrorMatches($readablePaths, $cutoff, $environment);
        $visibleMatches = array_slice($matches, 0, $limit);
        $ok = empty($matches) && empty($unreadable);

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => $ok,
                'minutes' => $minutes,
                'environment' => $environment,
                'include_all_environments' => (bool) $this->option('include-all-environments'),
                'cutoff' => $cutoff->toDateTimeString(),
                'files' => $readablePaths,
                'unreadable' => $unreadable,
                'match_count' => count($matches),
                'matches' => $visibleMatches,
                'truncated' => count($matches) > $limit,
            ], JSON_PRETTY_PRINT));

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Recent application log readiness');
        $this->newLine();

        if (empty($paths)) {
            $this->line('PASS No log files found in storage/logs.');

            return self::SUCCESS;
        }

        foreach ($unreadable as $path) {
            $this->line("FAIL {$path} is not a readable log file");
        }

        foreach ($visibleMatches as $match) {
            $this->line(sprintf(
                'FAIL %s:%d %s.%s %s',
                $match['file'],
                $match['line'],
                $match['environment'],
                $match['level'],
                $match['message'],
            ));
        }

        if (count($matches) > $limit) {
            $this->line(sprintf(
                'FAIL %d more recent application error(s) hidden by --limit=%d.',
                count($matches) - $limit,
                $limit,
            ));
        }

        if (! $ok) {
            $this->newLine();
            $this->error(count($matches).' recent application error(s) found.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'PASS No ERROR, CRITICAL, ALERT, or EMERGENCY entries found in the last %d minute(s)%s.',
            $minutes,
            $environment === null ? '' : " for {$environment}",
        ));

        return self::SUCCESS;
    }

    private function minutesOption(): ?int
    {
        $value = (string) $this->option('minutes');

        if (! ctype_digit($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    private function limitOption(): ?int
    {
        $value = (string) $this->option('limit');

        if (! ctype_digit($value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    private function environmentOption(): ?string
    {
        if ($this->option('include-all-environments')) {
            return null;
        }

        $value = $this->option('environment');

        if ($value === null || $value === '') {
            return (string) app()->environment();
        }

        $environment = trim((string) $value);

        return $environment === '' ? '' : $environment;
    }

    /**
     * @return list<string>
     */
    private function logPaths(): array
    {
        $configuredPaths = array_filter(
            array_map(fn (mixed $path) => trim((string) $path), (array) $this->option('path')),
        );

        if (! empty($configuredPaths)) {
            return array_values(array_unique($configuredPaths));
        }

        $paths = glob(storage_path('logs/*.log')) ?: [];

        return array_values(array_unique($paths));
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private function unreadablePaths(array $paths): array
    {
        return array_values(array_filter(
            $paths,
            fn (string $path) => ! is_file($path) || ! is_readable($path),
        ));
    }

    /**
     * @param  list<string>  $paths
     * @return list<array{file: string, line: int, timestamp: string, environment: string, level: string, message: string}>
     */
    private function recentErrorMatches(array $paths, Carbon $cutoff, ?string $environment): array
    {
        $matches = [];

        foreach ($paths as $path) {
            $file = new SplFileObject($path);
            $lineNumber = 0;

            while (! $file->eof()) {
                $lineNumber++;
                $line = rtrim((string) $file->fgets(), "\r\n");

                if (! preg_match(self::LOG_LINE_PATTERN, $line, $parts)) {
                    continue;
                }

                $channel = (string) $parts['channel'];

                if ($environment !== null && $channel !== $environment) {
                    continue;
                }

                $level = strtoupper($parts['level']);

                if (! in_array($level, self::BLOCKING_LEVELS, true)) {
                    continue;
                }

                try {
                    $timestamp = Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $parts['timestamp'],
                        config('app.timezone') ?: date_default_timezone_get(),
                    );
                } catch (Throwable) {
                    continue;
                }

                if ($timestamp->lessThan($cutoff)) {
                    continue;
                }

                $matches[] = [
                    'file' => $path,
                    'line' => $lineNumber,
                    'timestamp' => $timestamp->toDateTimeString(),
                    'environment' => $channel,
                    'level' => $level,
                    'message' => Str::limit(trim($parts['message']), 240),
                ];
            }
        }

        return $matches;
    }
}

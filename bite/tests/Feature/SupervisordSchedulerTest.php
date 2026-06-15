<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupervisordSchedulerTest extends TestCase
{
    private function supervisordConfig(): string
    {
        $path = base_path('docker/supervisord.conf');

        $this->assertFileExists($path, 'docker/supervisord.conf is missing.');

        return (string) file_get_contents($path);
    }

    public function test_supervisord_defines_a_scheduler_program(): void
    {
        $this->assertStringContainsString(
            '[program:scheduler]',
            $this->supervisordConfig(),
            'supervisord.conf must define a [program:scheduler] block so the Laravel scheduler runs in production.'
        );
    }

    public function test_scheduler_program_runs_schedule_work(): void
    {
        $this->assertMatchesRegularExpression(
            '/artisan\s+schedule:work/',
            $this->supervisordConfig(),
            'The scheduler program must invoke "php artisan schedule:work" to fire scheduled tasks (e.g. Order::cancelExpired).'
        );
    }

    public function test_scheduler_program_sets_working_directory(): void
    {
        $this->assertStringContainsString(
            'directory=/var/www/html',
            $this->supervisordConfig(),
            'The scheduler program must set directory=/var/www/html (the container WORKDIR) so "php artisan" resolves; without it schedule:work silently fails.'
        );
    }
}

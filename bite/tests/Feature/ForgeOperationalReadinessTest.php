<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ForgeOperationalReadinessTest extends TestCase
{
    private function readRepoFile(string $path): string
    {
        $fullPath = base_path($path);

        $this->assertFileExists($fullPath, "{$path} is missing.");

        return (string) file_get_contents($fullPath);
    }

    public function test_operations_runbook_names_forge_as_current_pilot_target(): void
    {
        $runbook = $this->readRepoFile('docs/OPERATIONS.md');

        $this->assertStringContainsString('Current production target: Laravel Forge', $runbook);
        $this->assertStringContainsString('deploy/forge-deploy.sh', $runbook);
        $this->assertStringContainsString('php artisan bite:production-check', $runbook);
        $this->assertStringNotContainsString(
            'Pushes to `main` trigger the full pipeline automatically',
            $runbook,
            'The operations runbook must not describe the paused Cloud Run GitHub Actions deploy as the active pilot path.'
        );
    }

    public function test_forge_runbook_covers_scheduler_and_local_image_backup_handoff(): void
    {
        $runbook = $this->readRepoFile('docs/OPERATIONS.md');

        $this->assertStringContainsString('php artisan schedule:run', $runbook);
        $this->assertStringContainsString('php artisan bite:schema-check', $runbook);
        $this->assertStringContainsString('php artisan bite:log-check --minutes=60', $runbook);
        $this->assertStringContainsString('php artisan bite:handoff-check', $runbook);
        $this->assertStringContainsString('live HTTP checks', $runbook);
        $this->assertStringContainsString('Pro/trial reports access', $runbook);
        $this->assertStringContainsString('authenticated owner/admin checks', $runbook);
        $this->assertStringContainsString('dashboard, POS, products, settings, reports, export, shift report, cash', $runbook);
        $this->assertStringContainsString('reconciliation, and billing.', $runbook);
        $this->assertStringContainsString('php artisan schedule:list --json', $runbook);
        $this->assertStringContainsString('orders.cancel-expired', $runbook);
        $this->assertStringContainsString('group-carts.clean-expired', $runbook);
        $this->assertStringContainsString('webhook-events.prune-processed', $runbook);
        $this->assertStringContainsString('storage/app/public', $runbook);
        $this->assertStringContainsString('public/storage', $runbook);
        $this->assertStringContainsString('scheduled MySQL dump', $runbook);
        $this->assertStringContainsString('deploy/forge-backup-database.sh', $runbook);
        $this->assertStringContainsString('deploy/forge-backup-storage.sh', $runbook);
        $this->assertStringContainsString('deploy/forge-restore-database-backup.sh', $runbook);
        $this->assertStringContainsString('deploy/forge-restore-storage-backup.sh', $runbook);
    }

    public function test_scheduled_maintenance_jobs_match_handoff_runbook(): void
    {
        $events = collect(app(Schedule::class)->events())->keyBy('description');

        $this->assertSame('* * * * *', $events->get('orders.cancel-expired')?->expression);
        $this->assertSame('0 * * * *', $events->get('group-carts.clean-expired')?->expression);
        $this->assertSame('20 3 * * *', $events->get('webhook-events.prune-processed')?->expression);
        $this->assertStringContainsString(
            'webhook-events:prune --days=30',
            (string) $events->get('webhook-events.prune-processed')?->command,
        );
    }

    public function test_development_and_env_guides_name_forge_as_active_pilot_path(): void
    {
        $developmentGuide = $this->readRepoFile('DEVELOPMENT.md');
        $localEnvExample = $this->readRepoFile('.env.example');
        $productionEnvExample = $this->readRepoFile('.env.production.example');

        $this->assertStringContainsString('Pilot production runs on Laravel Forge', $developmentGuide);
        $this->assertStringNotContainsString('Hosted on Google Cloud Run', $developmentGuide);

        $this->assertStringContainsString('Forge environment variables', $localEnvExample);
        $this->assertStringContainsString('Forge pilot production values live in .env.production.example', $localEnvExample);
        $this->assertStringContainsString('FILESYSTEM_DISK=public', $localEnvExample);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $localEnvExample);
        $this->assertStringNotContainsString('QUEUE_CONNECTION=database', $localEnvExample);
        $this->assertStringNotContainsString('Cloud Run environment variables', $localEnvExample);
        $this->assertStringNotContainsString('Cloud SQL in production', $localEnvExample);

        $this->assertStringContainsString('APP_ENV=production', $productionEnvExample);
        $this->assertStringContainsString('APP_URL=https://getbite.om', $productionEnvExample);
        $this->assertStringContainsString('SOURDOUGH_ADMIN_PASSWORD=<set-strong-handoff-password-before-seeding>', $productionEnvExample);
        $this->assertStringContainsString('QUEUE_CONNECTION=sync', $productionEnvExample);
        $this->assertStringContainsString('FILESYSTEM_DISK=public', $productionEnvExample);
        $this->assertStringContainsString('PAYMENT_PROVIDER=counter', $productionEnvExample);
        $this->assertStringContainsString('STRIPE_WEBHOOK_SECRET=', $productionEnvExample);
    }

    public function test_cloud_run_deployment_guide_is_marked_as_paused_reference(): void
    {
        $deploymentGuide = $this->readRepoFile('docs/DEPLOYMENT.md');

        $this->assertStringContainsString('Cloud Run Deployment Reference (Paused)', $deploymentGuide);
        $this->assertStringContainsString('Current pilot target is Laravel Forge', $deploymentGuide);
        $this->assertStringContainsString('active restaurant handoff', $deploymentGuide);
        $this->assertStringNotContainsString(
            'Production runs on **Google Cloud Run**',
            $deploymentGuide,
            'The paused Cloud Run reference must not read like the current restaurant handoff path.'
        );
    }

    public function test_forge_deployment_checklist_names_current_order_hardening_columns(): void
    {
        $runbook = $this->readRepoFile('docs/DEPLOYMENT-FORGE.md');

        $this->assertStringContainsString('php artisan bite:schema-check', $runbook);
        $this->assertStringContainsString('php artisan bite:log-check --minutes=60', $runbook);
        $this->assertStringContainsString('php artisan bite:handoff-check sourdough --minutes=60', $runbook);
        $this->assertStringContainsString('Pro/trial reports access plus live `/health`, guest menu, rendered product', $runbook);
        $this->assertStringContainsString('image URLs, QR SVG target, PIN screen HTTP checks, and authenticated', $runbook);
        $this->assertStringContainsString('owner/admin dashboard, POS, products, settings, reports, export, shift', $runbook);
        $this->assertStringContainsString('SOURDOUGH_ADMIN_PASSWORD', $runbook);
        $this->assertStringContainsString('admin@sourdough.om', $runbook);
        $this->assertStringNotContainsString('the 3 pilot columns', $runbook);
    }

    public function test_sourdough_live_smoke_checklist_covers_issue_31(): void
    {
        $operationsGuide = $this->readRepoFile('docs/OPERATIONS.md');
        $checklist = $this->readRepoFile('docs/SOURDOUGH-LIVE-SMOKE.md');

        $this->assertStringContainsString('docs/SOURDOUGH-LIVE-SMOKE.md', $operationsGuide);
        $this->assertStringContainsString('PIN login -> POS order -> cash payment -> KDS transitions -> guest QR order -> tracking', $checklist);
        $this->assertStringContainsString('iOS Safari', $checklist);
        $this->assertStringContainsString('Android Chrome', $checklist);
        $this->assertStringContainsString('Arabic / RTL', $checklist);
        $this->assertStringContainsString('Product image fallback + missing cover/logo', $checklist);
        $this->assertStringContainsString('https://getbite.om/health', $checklist);
        $this->assertStringContainsString('https://getbite.om/menu/sourdough', $checklist);
        $this->assertStringContainsString('php artisan bite:handoff-check sourdough --minutes=60', $checklist);
    }

    public function test_forge_guides_match_database_endpoint_readiness_gate(): void
    {
        $forgeGuide = $this->readRepoFile('docs/DEPLOYMENT-FORGE.md');
        $operationsGuide = $this->readRepoFile('docs/OPERATIONS.md');
        $productionEnvExample = $this->readRepoFile('.env.production.example');

        $this->assertStringContainsString('missing database host/socket or credentials', $forgeGuide);
        $this->assertStringContainsString('enabled PrintNode printing without API key/printer/HTTPS endpoint', $forgeGuide);
        $this->assertStringContainsString('incomplete PrintNode config when printing is enabled', $operationsGuide);
        $this->assertStringContainsString('missing database host/socket or credentials', $operationsGuide);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $productionEnvExample);
        $this->assertStringContainsString('DB_PASSWORD=<set-in-forge>', $productionEnvExample);
    }

    public function test_forge_deploy_script_runs_production_gate_before_migrations_and_reload(): void
    {
        $script = $this->readRepoFile('deploy/forge-deploy.sh');

        $storageLinkPosition = strpos($script, 'artisan storage:link');
        $gatePosition = strpos($script, 'artisan bite:production-check');
        $migratePosition = strpos($script, 'artisan migrate --force');
        $schemaPosition = strpos($script, 'artisan bite:schema-check');
        $reloadPosition = strpos($script, 'service "$FORGE_PHP_FPM" reload');

        $this->assertNotFalse($storageLinkPosition, 'Forge deploy script must expose public storage before handoff checks.');
        $this->assertNotFalse($gatePosition, 'Forge deploy script must run bite:production-check.');
        $this->assertNotFalse($migratePosition, 'Forge deploy script must run migrations.');
        $this->assertNotFalse($schemaPosition, 'Forge deploy script must validate post-migration schema.');
        $this->assertNotFalse($reloadPosition, 'Forge deploy script must reload PHP-FPM.');
        $this->assertLessThan($gatePosition, $storageLinkPosition, 'Public storage must be linked before the production gate runs.');
        $this->assertLessThan($migratePosition, $gatePosition, 'Production config must be checked before migrations.');
        $this->assertLessThan($schemaPosition, $migratePosition, 'Schema readiness must run after migrations.');
        $this->assertLessThan($reloadPosition, $schemaPosition, 'PHP-FPM must reload after schema readiness passes.');
        $this->assertStringNotContainsString('artisan storage:link || true', $script);
    }

    public function test_forge_deploy_cache_commands_are_supported(): void
    {
        try {
            $this->artisan('config:cache')->assertExitCode(0);
            $this->artisan('route:cache')->assertExitCode(0);
            $this->artisan('view:cache')->assertExitCode(0);
            $this->artisan('event:cache')->assertExitCode(0);
        } finally {
            $this->artisan('optimize:clear')->assertExitCode(0);
        }
    }

    public function test_forge_backup_scripts_cover_database_and_uploaded_images(): void
    {
        $databaseScriptPath = base_path('deploy/forge-backup-database.sh');
        $storageScriptPath = base_path('deploy/forge-backup-storage.sh');
        $databaseRestoreScriptPath = base_path('deploy/forge-restore-database-backup.sh');
        $storageRestoreScriptPath = base_path('deploy/forge-restore-storage-backup.sh');
        $databaseScript = $this->readRepoFile('deploy/forge-backup-database.sh');
        $storageScript = $this->readRepoFile('deploy/forge-backup-storage.sh');
        $databaseRestoreScript = $this->readRepoFile('deploy/forge-restore-database-backup.sh');
        $storageRestoreScript = $this->readRepoFile('deploy/forge-restore-storage-backup.sh');

        $this->assertTrue(is_executable($databaseScriptPath), 'Database backup script must be executable for Forge Scheduler.');
        $this->assertTrue(is_executable($storageScriptPath), 'Storage backup script must be executable for Forge Scheduler.');
        $this->assertTrue(is_executable($databaseRestoreScriptPath), 'Database restore drill script must be executable for handoff.');
        $this->assertTrue(is_executable($storageRestoreScriptPath), 'Storage restore drill script must be executable for handoff.');

        $this->assertStringContainsString('mysqldump', $databaseScript);
        $this->assertStringContainsString('command -v mysqldump', $databaseScript);
        $this->assertStringContainsString('--single-transaction', $databaseScript);
        $this->assertStringContainsString('MYSQL_PWD', $databaseScript);
        $this->assertStringNotContainsString('--password=', $databaseScript);
        $this->assertStringContainsString('BACKUP_S3_URI', $databaseScript);
        $this->assertStringContainsString('command -v aws', $databaseScript);

        $this->assertStringContainsString('storage/app/public', $storageScript);
        $this->assertStringContainsString('tar czf', $storageScript);
        $this->assertStringContainsString('BACKUP_S3_URI', $storageScript);
        $this->assertStringContainsString('command -v aws', $storageScript);

        $this->assertStringContainsString('RESTORE_DB_DATABASE', $databaseRestoreScript);
        $this->assertStringContainsString('Refusing to restore into configured app database', $databaseRestoreScript);
        $this->assertStringContainsString('gunzip -c "$archive" | mysql', $databaseRestoreScript);

        $this->assertStringContainsString('RESTORE_DRILL_DIR', $storageRestoreScript);
        $this->assertStringContainsString('tar xzf "$archive"', $storageRestoreScript);
        $this->assertStringContainsString('storage/app/public', $storageRestoreScript);
    }

    public function test_restaurant_handoff_record_template_keeps_secrets_out_of_git(): void
    {
        $template = $this->readRepoFile('docs/RESTAURANT-HANDOFF-RECORD.md');
        $gitignore = $this->readRepoFile('.gitignore');
        $operationsGuide = $this->readRepoFile('docs/OPERATIONS.md');
        $forgeGuide = $this->readRepoFile('docs/DEPLOYMENT-FORGE.md');

        $this->assertStringContainsString('password-manager item', $template);
        $this->assertStringContainsString('must not', $template);
        $this->assertStringContainsString('plaintext passwords', $template);
        $this->assertStringContainsString('Production Gate', $template);
        $this->assertStringContainsString('Restaurant Flow Proof', $template);
        $this->assertStringContainsString('Backup And Restore Proof', $template);
        $this->assertStringContainsString('php artisan bite:schema-check', $template);
        $this->assertStringContainsString('php artisan bite:log-check --minutes=60', $template);
        $this->assertStringContainsString('php artisan bite:handoff-check <restaurant-slug> --minutes=60', $template);
        $this->assertStringContainsString('reports access, owner/admin pages, live health/menu/product image', $template);
        $this->assertStringContainsString('Guest menu product image URLs checked as HTTP 200 `image/*` responses', $template);
        $this->assertStringContainsString('Owner/admin dashboard, POS, products, settings, reports, export, shift report,', $template);
        $this->assertStringContainsString('deploy/forge-restore-database-backup.sh', $template);
        $this->assertStringContainsString('deploy/forge-restore-storage-backup.sh', $template);
        $this->assertStringContainsString('docs/handoffs/**', $gitignore);
        $this->assertStringContainsString('!docs/handoffs/.gitkeep', $gitignore);
        $this->assertStringContainsString('docs/RESTAURANT-HANDOFF-RECORD.md', $operationsGuide);
        $this->assertStringContainsString('docs/RESTAURANT-HANDOFF-RECORD.md', $forgeGuide);
    }

    public function test_filled_restaurant_handoff_records_are_gitignored(): void
    {
        foreach ([
            'docs/handoffs/sourdough.md',
            'docs/handoffs/sourdough.json',
            'docs/handoffs/sourdough/secrets.md',
        ] as $path) {
            $this->assertSame(
                0,
                $this->gitCheckIgnore($path),
                "{$path} must be ignored so filled restaurant handoff records cannot be committed by accident.",
            );
        }

        $this->assertSame(
            1,
            $this->gitCheckIgnore('docs/handoffs/.gitkeep'),
            'The handoff directory placeholder must remain trackable.',
        );
    }

    public function test_forge_handoff_uses_laravel_app_subdirectory(): void
    {
        $script = $this->readRepoFile('deploy/forge-deploy.sh');
        $forgeGuide = $this->readRepoFile('docs/DEPLOYMENT-FORGE.md');
        $operationsGuide = $this->readRepoFile('docs/OPERATIONS.md');

        $this->assertStringContainsString('cd "/home/forge/$FORGE_SITE_NAME/bite"', $script);
        $this->assertStringContainsString('web directory `/bite/public`', $forgeGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-backup-database.sh', $forgeGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-backup-storage.sh', $forgeGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-restore-database-backup.sh', $forgeGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-restore-storage-backup.sh', $forgeGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-backup-database.sh', $operationsGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-backup-storage.sh', $operationsGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-restore-database-backup.sh', $operationsGuide);
        $this->assertStringContainsString('/home/forge/getbite.om/bite/deploy/forge-restore-storage-backup.sh', $operationsGuide);
    }

    private function gitCheckIgnore(string $path): int
    {
        $command = sprintf('git check-ignore --quiet -- %s', escapeshellarg($path));
        exec($command, $output, $exitCode);

        return $exitCode;
    }
}

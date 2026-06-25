<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ForgeBackupRestoreScriptsTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            File::deleteDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_storage_backup_archive_can_be_restored_into_drill_directory(): void
    {
        $root = $this->temporaryDirectory('storage-restore');
        $appRoot = "{$root}/app";
        $backupDir = "{$root}/backups";
        $restoreDir = "{$root}/restore-drill";

        File::ensureDirectoryExists("{$appRoot}/storage/app/public/products");
        File::put("{$appRoot}/storage/app/public/products/hummus.webp", 'image-bytes');

        $backup = $this->runScript('deploy/forge-backup-storage.sh', [
            'APP_ROOT' => $appRoot,
            'BACKUP_DIR' => $backupDir,
        ]);

        $this->assertTrue($backup->isSuccessful(), $backup->getErrorOutput());

        $archives = glob("{$backupDir}/getbite-storage-*.tgz") ?: [];
        $this->assertCount(1, $archives);

        $restore = $this->runScript('deploy/forge-restore-storage-backup.sh', [
            'APP_ROOT' => $appRoot,
            'BACKUP_DIR' => $backupDir,
            'RESTORE_DRILL_DIR' => $restoreDir,
        ], [$archives[0]]);

        $this->assertTrue($restore->isSuccessful(), $restore->getErrorOutput());
        $this->assertFileExists("{$restoreDir}/storage/app/public/products/hummus.webp");
        $this->assertSame('image-bytes', File::get("{$restoreDir}/storage/app/public/products/hummus.webp"));
        $this->assertStringContainsString('Storage backup restore drill passed', $restore->getOutput());
    }

    public function test_database_restore_drill_imports_backup_into_explicit_throwaway_database(): void
    {
        $root = $this->temporaryDirectory('database-restore');
        $backupDir = "{$root}/backups";
        $fakeBin = "{$root}/bin";
        $capturedSql = "{$root}/captured.sql";
        $capturedArgs = "{$root}/captured-args.txt";
        $archive = "{$backupDir}/getbite-db-2026-06-25-120000.sql.gz";

        File::ensureDirectoryExists($backupDir);
        File::ensureDirectoryExists($fakeBin);
        File::put($archive, gzencode('CREATE TABLE restore_probe (id int);'));
        File::put("{$fakeBin}/mysql", <<<'BASH'
#!/usr/bin/env bash
printf '%s\n' "$@" > "$MYSQL_ARGS_PATH"
cat > "$MYSQL_CAPTURE_PATH"
BASH);
        chmod("{$fakeBin}/mysql", 0755);

        $restore = $this->runScript('deploy/forge-restore-database-backup.sh', [
            'BACKUP_DIR' => $backupDir,
            'DB_DATABASE' => 'getbite',
            'MYSQL_ARGS_PATH' => $capturedArgs,
            'MYSQL_CAPTURE_PATH' => $capturedSql,
            'PATH' => $fakeBin.PATH_SEPARATOR.getenv('PATH'),
            'RESTORE_DB_DATABASE' => 'getbite_restore_drill',
        ], [$archive]);

        $this->assertTrue($restore->isSuccessful(), $restore->getErrorOutput());
        $this->assertSame('CREATE TABLE restore_probe (id int);', File::get($capturedSql));
        $this->assertStringContainsString('getbite_restore_drill', File::get($capturedArgs));
        $this->assertStringContainsString('Database backup restore drill passed', $restore->getOutput());
    }

    public function test_database_restore_drill_refuses_configured_app_database(): void
    {
        $root = $this->temporaryDirectory('database-restore-guard');
        $backupDir = "{$root}/backups";
        $fakeBin = "{$root}/bin";
        $archive = "{$backupDir}/getbite-db-2026-06-25-120000.sql.gz";

        File::ensureDirectoryExists($backupDir);
        File::ensureDirectoryExists($fakeBin);
        File::put($archive, gzencode('CREATE TABLE restore_probe (id int);'));
        File::put("{$fakeBin}/mysql", "#!/usr/bin/env bash\nexit 0\n");
        chmod("{$fakeBin}/mysql", 0755);

        $restore = $this->runScript('deploy/forge-restore-database-backup.sh', [
            'BACKUP_DIR' => $backupDir,
            'DB_DATABASE' => 'getbite',
            'PATH' => $fakeBin.PATH_SEPARATOR.getenv('PATH'),
            'RESTORE_DB_DATABASE' => 'getbite',
        ], [$archive]);

        $this->assertFalse($restore->isSuccessful());
        $this->assertStringContainsString('Refusing to restore into configured app database: getbite', $restore->getErrorOutput());
    }

    /**
     * @param  array<string, string>  $environment
     * @param  list<string>  $arguments
     */
    private function runScript(string $script, array $environment, array $arguments = []): Process
    {
        $process = new Process(
            array_merge(['bash', base_path($script)], $arguments),
            base_path(),
            $environment,
        );
        $process->setTimeout(30);
        $process->run();

        return $process;
    }

    private function temporaryDirectory(string $label): string
    {
        $directory = storage_path('framework/testing/'.$label.'-'.Str::uuid());
        File::ensureDirectoryExists($directory);
        $this->temporaryDirectories[] = $directory;

        return $directory;
    }
}

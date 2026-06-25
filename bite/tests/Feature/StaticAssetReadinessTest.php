<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaticAssetReadinessTest extends TestCase
{
    public function test_manifest_icon_files_exist(): void
    {
        $manifestPath = public_path('manifest.json');

        $this->assertFileExists($manifestPath);

        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertNotEmpty($manifest['icons'] ?? [], 'The PWA manifest must declare installable icons.');

        foreach ($manifest['icons'] as $icon) {
            $src = $icon['src'] ?? null;

            $this->assertIsString($src);
            $this->assertPublicAssetExists($src, "Manifest icon [{$src}] is missing from public/.");
        }
    }

    public function test_public_asset_references_exist(): void
    {
        $missing = [];

        foreach ($this->staticAssetFilesToScan() as $file) {
            if (! is_file($file)) {
                continue;
            }

            foreach ($this->extractStaticPublicAssetPaths(file_get_contents($file)) as $path) {
                $publicPath = public_path(ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/'));

                if (! file_exists($publicPath)) {
                    $missing[] = str_replace(base_path().'/', '', $file).": {$path}";
                }
            }
        }

        $this->assertSame([], $missing, 'Every root-relative static asset reference must resolve under public/.');
    }

    public function test_font_asset_references_exist_and_are_not_empty(): void
    {
        $fontPaths = [];

        foreach ($this->staticAssetFilesToScan() as $file) {
            if (! is_file($file)) {
                continue;
            }

            foreach ($this->extractStaticPublicAssetPaths(file_get_contents($file)) as $path) {
                if (str_starts_with($path, '/fonts/')) {
                    $fontPaths[] = $path;
                }
            }
        }

        $fontPaths = array_values(array_unique($fontPaths));

        $this->assertNotEmpty($fontPaths, 'No public font references were found to validate.');

        $missingOrEmpty = [];

        foreach ($fontPaths as $fontPath) {
            $publicPath = public_path(ltrim($fontPath, '/'));

            if (! is_file($publicPath) || filesize($publicPath) === 0) {
                $missingOrEmpty[] = $fontPath;
            }
        }

        $this->assertSame([], $missingOrEmpty, 'Every referenced public font must exist and be non-empty.');
    }

    public function test_service_worker_precaches_only_public_install_assets(): void
    {
        $serviceWorkerPath = public_path('sw.js');

        $this->assertFileExists($serviceWorkerPath);
        $this->get('/offline')->assertOk();
        $this->assertFileExists(public_path('manifest.json'));

        $serviceWorker = file_get_contents($serviceWorkerPath);

        preg_match('/const PRECACHE_URLS = \[(.*?)\];/s', $serviceWorker, $matches);

        $this->assertNotEmpty($matches, 'The service worker must declare PRECACHE_URLS.');

        $precacheList = $matches[1];

        $this->assertStringContainsString('OFFLINE_URL', $precacheList);
        $this->assertStringContainsString("'/manifest.json'", $precacheList);

        foreach (['/dashboard', '/pos', '/kds', '/billing', '/profile', '/onboarding', '/admin'] as $protectedPath) {
            $this->assertStringNotContainsString("'{$protectedPath}'", $precacheList);
            $this->assertStringNotContainsString("\"{$protectedPath}\"", $precacheList);
        }
    }

    public function test_admin_qr_codes_do_not_depend_on_external_image_services(): void
    {
        foreach ([
            resource_path('views/livewire/shop-dashboard.blade.php'),
            resource_path('views/livewire/onboarding-wizard.blade.php'),
            resource_path('views/livewire/shop-settings.blade.php'),
        ] as $view) {
            $this->assertFileExists($view);

            $contents = file_get_contents($view);

            $this->assertStringNotContainsString('api.qrserver.com', $contents);
            $this->assertStringContainsString("route('guest.menu.qr'", $contents);
        }
    }

    /**
     * @return list<string>
     */
    private function extractStaticPublicAssetPaths(string $contents): array
    {
        $paths = [];
        $patterns = [
            '/url\(\s*[\'"]?(\/(?!\/)[^)"\'\s?#]+(?:[?#][^)"\']*)?)[\'"]?\s*\)/i',
            '/\b(?:asset|url)\(\s*[\'"](\/(?!\/)[^\'"]+\.[a-z0-9]{2,12})(?:[?#][^\'"]*)?[\'"]\s*\)/i',
            '/\b(?:src|href|content)=["\'](\/(?!\/)[^"\']+\.[a-z0-9]{2,12})(?:[?#][^"\']*)?["\']/i',
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $contents, $matches);

            foreach ($matches[1] as $path) {
                $path = parse_url($path, PHP_URL_PATH) ?: $path;

                if ($this->isStaticPublicAssetPath($path)) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function isStaticPublicAssetPath(string $path): bool
    {
        return preg_match('/\.(?:css|js|mjs|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|otf|json|webmanifest)$/i', $path) === 1;
    }

    /**
     * @return list<string>
     */
    private function staticAssetFilesToScan(): array
    {
        $builtCssFiles = glob(public_path('build/assets/*.css')) ?: [];

        $this->assertNotEmpty($builtCssFiles, 'Run npm run build before handing off production assets.');

        return array_merge(
            glob(resource_path('css/*.css')) ?: [],
            [
                resource_path('views/welcome.blade.php'),
                resource_path('views/legal/privacy.blade.php'),
                resource_path('views/legal/terms.blade.php'),
            ],
            $builtCssFiles,
        );
    }

    private function assertPublicAssetExists(string $path, string $message): void
    {
        $this->assertFileExists(public_path(ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/')), $message);
    }
}

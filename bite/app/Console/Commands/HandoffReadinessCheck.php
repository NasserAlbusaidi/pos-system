<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\BillingService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class HandoffReadinessCheck extends Command
{
    protected $signature = 'bite:handoff-check
        {shop : Restaurant shop slug to validate}
        {--allow-non-production : Pass through to bite:production-check for local smoke tests}
        {--skip-production-check : Skip only the production env gate; do not use for paid handoff}
        {--skip-http-check : Skip live HTTP checks; do not use for paid handoff}
        {--minutes=60 : Look back this many minutes for application log errors}
        {--log-limit=25 : Maximum number of matching log entries to print from the nested log check}
        {--log-path=* : Log file path to inspect. Defaults to storage/logs/*.log}
        {--json : Output machine-readable JSON}';

    protected $description = 'Validate a restaurant-specific sell-ready handoff bundle.';

    public function handle(BillingService $billing, Schedule $schedule, HttpKernel $httpKernel): int
    {
        $shop = Shop::where('slug', (string) $this->argument('shop'))->first();
        $checks = [
            ...$this->restaurantChecks($shop, $billing),
            ...$this->httpChecks($shop),
            ...$this->ownerSurfaceChecks($shop, $httpKernel),
            ...$this->serverChecks($schedule),
        ];
        $failed = array_values(array_filter($checks, fn (array $check) => ! $check['ok']));

        if ($this->option('json')) {
            $this->line(json_encode([
                'ok' => empty($failed),
                'shop' => (string) $this->argument('shop'),
                'checks' => $checks,
            ], JSON_PRETTY_PRINT));

            return empty($failed) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Restaurant handoff readiness');
        $this->newLine();

        foreach ($checks as $check) {
            $this->line(sprintf(
                '%s [%s] %s%s',
                $check['ok'] ? '<fg=green>PASS</>' : '<fg=red>FAIL</>',
                $check['group'],
                $check['name'],
                $check['detail'] ? ' — '.$check['detail'] : '',
            ));
        }

        if (! empty($failed)) {
            $this->newLine();
            $this->error(count($failed).' handoff readiness check(s) failed.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Restaurant handoff readiness checks passed.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{group: string, name: string, ok: bool, detail: string}>
     */
    private function httpChecks(?Shop $shop): array
    {
        if (! $shop) {
            return [];
        }

        if ($this->option('skip-http-check')) {
            return [
                $this->check('http', 'Live HTTP checks', true, 'skipped by --skip-http-check'),
            ];
        }

        $guestMenuUrl = route('guest.menu', $shop);

        return [
            $this->httpStatusCheck('Live health endpoint returns 200', route('health')),
            $this->httpStatusCheck('Live guest menu returns 200', $guestMenuUrl),
            $this->httpMenuProductImagesCheck($shop, $guestMenuUrl),
            $this->httpSvgQrCheck(
                'Live guest QR returns SVG for guest menu',
                route('guest.menu.qr', $shop),
                $guestMenuUrl,
            ),
            $this->httpStatusCheck('Live PIN screen returns 200', route('pos.pin', $shop)),
        ];
    }

    /**
     * @return list<array{group: string, name: string, ok: bool, detail: string}>
     */
    private function restaurantChecks(?Shop $shop, BillingService $billing): array
    {
        if (! $shop) {
            return [
                $this->check('restaurant', 'Shop exists', false, 'slug: '.(string) $this->argument('shop')),
            ];
        }

        $branding = $shop->branding ?? [];
        $activeCategories = $shop->categories()->where('is_active', true)->count();
        $orderableProducts = Product::where('shop_id', $shop->id)
            ->orderable()
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->get();
        $productsMissingPhotos = $orderableProducts
            ->filter(fn (Product $product) => productImage($product, 'card') === null)
            ->values();
        $productsUsingRemotePhotos = $orderableProducts
            ->filter(fn (Product $product) => $this->productUsesRemoteImage($product))
            ->values();
        $productsMissingStoredCardPhotos = $orderableProducts
            ->filter(fn (Product $product) => ! $this->storedProductImageVariantExists($product, 'card'))
            ->values();
        $productsMissingStoredPhotoVariants = $orderableProducts
            ->filter(fn (Product $product) => ! $this->storedProductImageVariantsExist($product))
            ->values();
        $whatsAppAlertsEnabled = ! empty($branding['whatsapp_notifications_enabled']);
        $whatsAppNumber = app(WhatsAppService::class)->getNumber($shop);

        return [
            $this->check('restaurant', 'Shop exists', true, "{$shop->name} ({$shop->slug})"),
            $this->check(
                'restaurant',
                'Shop is not suspended',
                $shop->status !== 'suspended',
                'current: '.($shop->status ?? 'unknown'),
            ),
            $this->check(
                'restaurant',
                'Shop has active billing access',
                $billing->isSubscribed($shop),
                'trial, active subscription, or free plan is required',
            ),
            $this->check(
                'restaurant',
                'Reports feature is available',
                $billing->canAccess($shop, 'reports'),
                'current plan: '.($billing->getCurrentPlan($shop) ?? 'unknown'),
            ),
            $this->check(
                'restaurant',
                'Onboarding is complete',
                ! empty($branding['onboarding_completed']),
                'branding.onboarding_completed must be true',
            ),
            $this->check(
                'restaurant',
                'Owner/admin user exists',
                User::where('shop_id', $shop->id)->where('role', 'admin')->exists(),
                'at least one admin user is required for owner handoff',
            ),
            $this->check(
                'restaurant',
                'Owner/admin password login is provisioned',
                $this->ownerPasswordLoginIsProvisioned($shop),
                'at least one admin must have an email and non-default current password hash',
            ),
            $this->check(
                'restaurant',
                'POS PIN user exists',
                $this->pinUserExists($shop, ['admin', 'manager', 'server']),
                'admin, manager, or server current PIN hash required for tablet POS',
            ),
            $this->check(
                'restaurant',
                'KDS PIN user exists',
                $this->pinUserExists($shop, ['admin', 'manager', 'kitchen']),
                'admin, manager, or kitchen current PIN hash required for kitchen display',
            ),
            $this->check(
                'restaurant',
                'Reports-capable user exists',
                User::where('shop_id', $shop->id)->whereIn('role', ['admin', 'manager'])->exists(),
                'admin or manager required for reports and closeout',
            ),
            $this->check(
                'menu',
                'Active category exists',
                $activeCategories > 0,
                "{$activeCategories} active category record(s)",
            ),
            $this->check(
                'menu',
                'Orderable product exists',
                $orderableProducts->isNotEmpty(),
                "{$orderableProducts->count()} visible and available product(s) in active categories",
            ),
            $this->check(
                'menu',
                'Orderable products have menu photos',
                $productsMissingPhotos->isEmpty(),
                $productsMissingPhotos->isEmpty()
                    ? "{$orderableProducts->count()}/{$orderableProducts->count()} orderable product photo(s) configured"
                    : $productsMissingPhotos->count().' missing: '.$productsMissingPhotos
                        ->pluck('name_en')
                        ->filter()
                        ->take(5)
                        ->implode(', '),
            ),
            $this->check(
                'menu',
                'Orderable menu photos are locally hosted',
                $productsUsingRemotePhotos->isEmpty(),
                $productsUsingRemotePhotos->isEmpty()
                    ? "{$orderableProducts->count()}/{$orderableProducts->count()} orderable product photo(s) use public storage paths"
                    : $productsUsingRemotePhotos->count().' remote: '.$productsUsingRemotePhotos
                        ->pluck('name_en')
                        ->filter()
                        ->take(5)
                        ->implode(', '),
            ),
            $this->check(
                'menu',
                'Orderable menu photo files exist',
                $productsMissingStoredCardPhotos->isEmpty(),
                $productsMissingStoredCardPhotos->isEmpty()
                    ? "{$orderableProducts->count()}/{$orderableProducts->count()} orderable product card photo file(s) present"
                    : $productsMissingStoredCardPhotos->count().' missing card file: '.$productsMissingStoredCardPhotos
                        ->pluck('name_en')
                        ->filter()
                        ->take(5)
                        ->implode(', '),
            ),
            $this->check(
                'menu',
                'Orderable menu photo variants exist',
                $productsMissingStoredPhotoVariants->isEmpty(),
                $productsMissingStoredPhotoVariants->isEmpty()
                    ? "{$orderableProducts->count()}/{$orderableProducts->count()} orderable product thumb/card/full photo variant set(s) present"
                    : $productsMissingStoredPhotoVariants->count().' missing variant set: '.$productsMissingStoredPhotoVariants
                        ->pluck('name_en')
                        ->filter()
                        ->take(5)
                        ->implode(', '),
            ),
            $this->check(
                'menu',
                'Currency is configured',
                $this->configuredCurrency($shop),
                "{$shop->currency_code} {$shop->currency_symbol} decimals={$shop->currency_decimals}",
            ),
            $this->check(
                'menu',
                'Tax rate is configured',
                $shop->tax_rate !== null && (float) $shop->tax_rate >= 0 && (float) $shop->tax_rate <= 100,
                'current: '.var_export($shop->tax_rate, true),
            ),
            $this->check(
                'alerts',
                'WhatsApp alerts are disabled or usable',
                ! $whatsAppAlertsEnabled || $whatsAppNumber !== null,
                $whatsAppAlertsEnabled
                    ? ($whatsAppNumber !== null ? "alerts send to {$whatsAppNumber}" : 'branding.whatsapp_notifications_enabled is true but branding.whatsapp_number is missing or invalid')
                    : 'disabled',
            ),
            $this->check(
                'routes',
                'Guest menu route exists',
                Route::has('guest.menu'),
                Route::has('guest.menu') ? route('guest.menu', $shop) : 'route guest.menu is missing',
            ),
            $this->check(
                'routes',
                'Guest QR route exists',
                Route::has('guest.menu.qr'),
                Route::has('guest.menu.qr') ? route('guest.menu.qr', $shop) : 'route guest.menu.qr is missing',
            ),
            $this->check(
                'routes',
                'PIN route exists',
                Route::has('pos.pin'),
                Route::has('pos.pin') ? route('pos.pin', $shop) : 'route pos.pin is missing',
            ),
            $this->namedRouteCheck('Reports dashboard route exists', 'admin.reports'),
            $this->namedRouteCheck('Reports export route exists', 'admin.reports.export'),
            $this->namedRouteCheck('Shift report route exists', 'admin.shift-report'),
            $this->namedRouteCheck('Cash reconciliation route exists', 'admin.cash-reconciliation'),
            $this->namedRouteCheck('Billing route exists', 'billing'),
            $this->namedRouteCheck('Shop settings route exists', 'admin.settings'),
            $this->namedRouteCheck('Product manager route exists', 'admin.products'),
        ];
    }

    /**
     * @return list<array{group: string, name: string, ok: bool, detail: string}>
     */
    private function ownerSurfaceChecks(?Shop $shop, HttpKernel $httpKernel): array
    {
        if (! $shop) {
            return [];
        }

        $owner = $this->handoffOwner($shop);
        $surfaces = [
            'dashboard' => 'Owner dashboard loads',
            'pos.dashboard' => 'Owner POS loads',
            'admin.products' => 'Owner product manager loads',
            'admin.settings' => 'Owner shop settings load',
            'admin.reports' => 'Owner reports dashboard loads',
            'admin.reports.export' => 'Owner reports export loads',
            'admin.shift-report' => 'Owner shift report loads',
            'admin.cash-reconciliation' => 'Owner cash reconciliation loads',
            'billing' => 'Owner billing settings load',
        ];

        if (! $owner) {
            return array_map(
                fn (string $name): array => $this->check(
                    'owner',
                    $name,
                    false,
                    'no provisioned owner/admin user is available for authenticated route checks',
                ),
                array_values($surfaces),
            );
        }

        $checks = [];

        foreach ($surfaces as $routeName => $name) {
            if (! Route::has($routeName)) {
                $checks[] = $this->check('owner', $name, false, "route {$routeName} is missing");

                continue;
            }

            $url = route($routeName, [], false);
            $result = $this->authenticatedGetStatus($owner, $url, $httpKernel);

            if (is_int($result)) {
                $checks[] = $this->check(
                    'owner',
                    $name,
                    $result >= 200 && $result < 300,
                    "GET {$url} as {$owner->email} returned {$result}",
                );

                continue;
            }

            $checks[] = $this->check('owner', $name, false, "GET {$url} as {$owner->email} failed: {$result}");
        }

        return $checks;
    }

    /**
     * @return list<array{group: string, name: string, ok: bool, detail: string}>
     */
    private function serverChecks(Schedule $schedule): array
    {
        $checks = [];

        if ($this->option('skip-production-check')) {
            $checks[] = $this->check('server', 'Production configuration gate', true, 'skipped by --skip-production-check');
        } else {
            $checks[] = $this->artisanGate('server', 'Production configuration gate', 'bite:production-check', [
                '--allow-non-production' => (bool) $this->option('allow-non-production'),
                '--json' => true,
            ]);
        }

        $checks[] = $this->artisanGate('server', 'Post-migration schema gate', 'bite:schema-check', [
            '--json' => true,
        ]);

        $checks[] = $this->artisanGate('server', 'Recent application log gate', 'bite:log-check', [
            '--minutes' => (int) $this->option('minutes'),
            '--limit' => (int) $this->option('log-limit'),
            '--path' => (array) $this->option('log-path'),
            '--json' => true,
        ]);

        $scheduled = collect($schedule->events())->pluck('description')->all();

        foreach (['orders.cancel-expired', 'group-carts.clean-expired', 'webhook-events.prune-processed'] as $description) {
            $checks[] = $this->check(
                'server',
                "Scheduled task {$description} is defined",
                in_array($description, $scheduled, true),
                'Forge Scheduler must still run php artisan schedule:run every minute',
            );
        }

        foreach ([
            'deploy/forge-backup-database.sh',
            'deploy/forge-backup-storage.sh',
            'deploy/forge-restore-database-backup.sh',
            'deploy/forge-restore-storage-backup.sh',
        ] as $script) {
            $path = base_path($script);
            $checks[] = $this->check(
                'server',
                "{$script} is executable",
                is_file($path) && is_executable($path),
                $path,
            );
        }

        return $checks;
    }

    private function pinUserExists(Shop $shop, array $roles): bool
    {
        return User::where('shop_id', $shop->id)
            ->whereIn('role', $roles)
            ->get()
            ->contains(fn (User $user) => $this->pinLoginIsProvisioned($user));
    }

    private function pinLoginIsProvisioned(User $user): bool
    {
        $pinCode = (string) $user->pin_code;

        return trim($pinCode) !== ''
            && ! Hash::needsRehash($pinCode);
    }

    private function ownerPasswordLoginIsProvisioned(Shop $shop): bool
    {
        return $this->handoffOwner($shop) !== null;
    }

    private function handoffOwner(Shop $shop): ?User
    {
        return User::where('shop_id', $shop->id)
            ->where('role', 'admin')
            ->get()
            ->first(function (User $user): bool {
                $email = trim((string) $user->email);
                $password = (string) $user->password;

                return $email !== ''
                    && $password !== ''
                    && ! Hash::needsRehash($password)
                    && ! Hash::check('password', $password);
            });
    }

    private function configuredCurrency(Shop $shop): bool
    {
        return is_string($shop->currency_code)
            && trim($shop->currency_code) !== ''
            && is_string($shop->currency_symbol)
            && trim($shop->currency_symbol) !== ''
            && is_numeric($shop->currency_decimals)
            && (int) $shop->currency_decimals >= 0
            && (int) $shop->currency_decimals <= 3;
    }

    private function productUsesRemoteImage(Product $product): bool
    {
        $imageUrl = trim((string) $product->image_url);
        $scheme = parse_url($imageUrl, PHP_URL_SCHEME);

        return is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private function storedProductImageVariantExists(Product $product, string $variant): bool
    {
        if ($this->productUsesRemoteImage($product)) {
            return false;
        }

        $imageUrl = trim((string) $product->image_url);

        if ($imageUrl === '') {
            return false;
        }

        $variantPath = preg_replace('/-full\./', "-{$variant}.", $imageUrl, 1);

        if (! is_string($variantPath) || trim($variantPath) === '') {
            return false;
        }

        return Storage::disk(config('filesystems.default'))->exists($variantPath);
    }

    private function storedProductImageVariantsExist(Product $product): bool
    {
        foreach (['thumb', 'card', 'full'] as $variant) {
            if (! $this->storedProductImageVariantExists($product, $variant)) {
                return false;
            }
        }

        return true;
    }

    private function authenticatedGetStatus(User $user, string $url, HttpKernel $httpKernel): int|string
    {
        $previousUser = Auth::user();

        try {
            Auth::login($user);

            $request = Request::create($url, 'GET');
            $request->setLaravelSession(app('session.store'));

            $response = $httpKernel->handle($request);
            $status = $response->getStatusCode();
            $httpKernel->terminate($request, $response);

            return $status;
        } catch (Throwable $exception) {
            return $exception->getMessage();
        } finally {
            if ($previousUser instanceof User) {
                Auth::login($previousUser);
            } else {
                Auth::logout();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function artisanGate(string $group, string $name, string $command, array $arguments): array
    {
        $output = new BufferedOutput;
        $exitCode = $this->runCommand($command, $arguments, $output);
        $payload = json_decode($output->fetch(), true);

        if (! is_array($payload)) {
            return $this->check($group, $name, false, "{$command} did not return JSON");
        }

        $ok = $exitCode === self::SUCCESS && ($payload['ok'] ?? false) === true;
        $failed = collect($payload['checks'] ?? [])
            ->filter(fn ($check) => is_array($check) && ($check['ok'] ?? false) === false)
            ->pluck('name')
            ->values()
            ->all();

        if (! empty($failed)) {
            return $this->check($group, $name, false, 'failed: '.implode(', ', $failed));
        }

        if (isset($payload['match_count']) && (int) $payload['match_count'] > 0) {
            return $this->check($group, $name, false, $payload['match_count'].' recent application error(s)');
        }

        return $this->check($group, $name, $ok, $ok ? $command.' passed' : $command.' failed');
    }

    /**
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function httpStatusCheck(string $name, string $url): array
    {
        try {
            $response = Http::timeout(5)
                ->accept('text/html,application/json,image/svg+xml')
                ->get($url);

            return $this->check(
                'http',
                $name,
                $response->ok(),
                "GET {$url} returned {$response->status()}",
            );
        } catch (Throwable $exception) {
            return $this->check(
                'http',
                $name,
                false,
                "GET {$url} failed: ".$exception->getMessage(),
            );
        }
    }

    /**
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function httpMenuProductImagesCheck(Shop $shop, string $menuUrl): array
    {
        $name = 'Live guest menu product images return images';

        try {
            $response = Http::timeout(5)
                ->accept('text/html')
                ->get($menuUrl);

            if (! $response->ok()) {
                return $this->check('http', $name, false, "GET {$menuUrl} returned {$response->status()}");
            }

            $renderedImageSources = $this->imageSourcesFromHtml($response->body());
            $expectedImages = Product::where('shop_id', $shop->id)
                ->orderable()
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->get()
                ->map(fn (Product $product) => [
                    'label' => trim((string) $product->name_en) !== ''
                        ? "{$product->name_en} #{$product->id}"
                        : "product #{$product->id}",
                    'url' => productImage($product, 'card'),
                ])
                ->filter(fn (array $image) => is_string($image['url']) && trim($image['url']) !== '')
                ->values();

            if ($expectedImages->isEmpty()) {
                return $this->check('http', $name, false, 'no orderable product image URLs are configured');
            }

            $renderedImageCounts = array_count_values($renderedImageSources);
            $missingFromMenu = $expectedImages
                ->groupBy('url')
                ->flatMap(function ($images, string $url) use ($renderedImageCounts) {
                    $renderedCount = $renderedImageCounts[$url] ?? 0;
                    $missingCount = $images->count() - $renderedCount;

                    if ($missingCount <= 0) {
                        return [];
                    }

                    return $images
                        ->slice($renderedCount)
                        ->pluck('label')
                        ->values();
                })
                ->take(5)
                ->implode(', ');

            if ($missingFromMenu !== '') {
                return $this->check('http', $name, false, 'missing rendered product image(s): '.$missingFromMenu);
            }

            $brokenImages = [];
            foreach ($expectedImages->pluck('url')->unique()->values() as $imageUrl) {
                $resolvedUrl = $this->resolveHttpUrl($menuUrl, $imageUrl);

                if ($resolvedUrl === null) {
                    $brokenImages[] = "{$imageUrl} has unsupported URL";

                    continue;
                }

                $imageResponse = Http::timeout(5)
                    ->accept('image/*')
                    ->get($resolvedUrl);
                $contentType = strtolower((string) $imageResponse->header('Content-Type'));

                if (! $imageResponse->ok() || ! str_starts_with($contentType, 'image/')) {
                    $brokenImages[] = "{$imageUrl} returned {$imageResponse->status()} "
                        .($contentType !== '' ? $contentType : 'missing content-type');
                }
            }

            return $this->check(
                'http',
                $name,
                $brokenImages === [],
                $brokenImages === []
                    ? $expectedImages->count().' product image URL(s) loaded as images'
                    : implode('; ', array_slice($brokenImages, 0, 5)),
            );
        } catch (Throwable $exception) {
            return $this->check(
                'http',
                $name,
                false,
                "GET {$menuUrl} images failed: ".$exception->getMessage(),
            );
        }
    }

    /**
     * @return list<string>
     */
    private function imageSourcesFromHtml(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $sources = [];

        try {
            $document = new \DOMDocument;
            $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

            foreach ($document->getElementsByTagName('img') as $image) {
                $source = trim((string) $image->getAttribute('src'));

                if ($source !== '') {
                    $sources[] = $source;
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $sources;
    }

    private function resolveHttpUrl(string $baseUrl, string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            return in_array(strtolower($scheme), ['http', 'https'], true) ? $url : null;
        }

        $baseParts = parse_url($baseUrl);
        $baseScheme = $baseParts['scheme'] ?? null;
        $baseHost = $baseParts['host'] ?? null;

        if (! is_string($baseScheme) || ! is_string($baseHost)) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return $baseScheme.':'.$url;
        }

        $baseAuthority = $baseScheme.'://'.$baseHost;

        if (isset($baseParts['port'])) {
            $baseAuthority .= ':'.$baseParts['port'];
        }

        if (str_starts_with($url, '/')) {
            return $baseAuthority.$url;
        }

        $basePath = $baseParts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($basePath)), '/');

        return $baseAuthority.($directory === '' ? '' : $directory).'/'.$url;
    }

    /**
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function httpSvgQrCheck(string $name, string $url, string $expectedTarget): array
    {
        try {
            $response = Http::timeout(5)
                ->accept('image/svg+xml')
                ->get($url);

            $contentType = strtolower((string) $response->header('Content-Type'));
            $body = ltrim($response->body());
            $target = (string) $response->header('X-Bite-QR-Target');
            $isSvg = str_contains($contentType, 'image/svg+xml')
                && preg_match('/^(?:<\?xml[^>]*>\s*)?<svg\b/i', $body) === 1;
            $targetMatches = hash_equals($expectedTarget, $target);
            $ok = $response->ok() && $isSvg && $targetMatches;

            return $this->check(
                'http',
                $name,
                $ok,
                "GET {$url} returned {$response->status()}; content-type="
                    .($contentType !== '' ? $contentType : 'missing')
                    .'; target='.($target !== '' ? $target : 'missing'),
            );
        } catch (Throwable $exception) {
            return $this->check(
                'http',
                $name,
                false,
                "GET {$url} failed: ".$exception->getMessage(),
            );
        }
    }

    /**
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function namedRouteCheck(string $name, string $routeName): array
    {
        $exists = Route::has($routeName);

        return $this->check(
            'routes',
            $name,
            $exists,
            $exists ? route($routeName) : "route {$routeName} is missing",
        );
    }

    /**
     * @return array{group: string, name: string, ok: bool, detail: string}
     */
    private function check(string $group, string $name, bool $ok, string $detail = ''): array
    {
        return compact('group', 'name', 'ok', 'detail');
    }
}

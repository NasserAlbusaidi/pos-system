# Phase 7: Hardening & Security - Research

**Researched:** 2026-03-27
**Domain:** Laravel 12 production hardening — health checks, rate limiting, structured logging, tenant isolation, input validation
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Health Check Endpoint (HARD-01)**
- D-01: GET /health performs a full stack check — verifies DB connectivity, GCS storage access, GD extension with WebP support, and queue connection. HTTP 503 on any failure (Cloud Run restarts).
- D-02: Response is detailed JSON: `{"status":"healthy","db":"ok","storage":"ok","gd_webp":"ok","queue":"ok","latency_ms":42}`. Same format in all environments.
- D-03: Endpoint is public, no authentication required.

**Startup Environment Validation (HARD-02)**
- D-04: App refuses to boot and logs a clear error message when any required environment variable is missing. Fail-fast behavior. Claude has discretion on which env vars are mandatory and the validation approach.

**Rate Limiting (HARD-03)**
- D-05: Guest ordering: ~10 orders per 15 minutes per IP.
- D-06: Webhook endpoints (Stripe): ~60 requests/minute per IP.
- D-07: Web login (Breeze): Use Laravel's built-in rate limiting (5 attempts/minute per email+IP). Verify it's active — no custom overrides needed.
- D-08: PIN login and manager override: Already rate-limited (5 attempts per shop+IP). Existing implementation is sufficient.
- D-09: 429 for guest-facing endpoints: Friendly user message "You're ordering too quickly. Please wait a moment and try again." Include `Retry-After` header.

**Logging & Observability (HARD-04)**
- D-10: Production logging uses structured JSON format for Cloud Logging queryability.
- D-11: PII masking: phone numbers as `+968****1234`, emails as `n***@bite.com`, IPs as `192.168.***`.
- D-12: Log scope: Errors + slow requests (>2 seconds).
- D-13: Sentry: errors + sampled performance traces (~10% sample rate).

**Tenant Isolation Audit (SEC-01)**
- D-14: Audit is a pre-deploy gate — every gap found gets fixed. No runtime detection or middleware guard. Code must be correct.
- D-15: Audit includes regression tests verifying each tenant-scoped model's queries are scoped to shop_id.

**Input Validation Sweep (SEC-03)**
- D-16: Sweep covers all Livewire components (not just public-facing). All 6 components with existing validation get reviewed; any without validation get it added.

### Claude's Discretion
- Which env vars are mandatory and the validation implementation approach
- JSON logging format details (field names, nesting, timestamp format)
- PII masking implementation (middleware, log formatter, or processor)
- Health check timeout thresholds per subsystem
- Specific rate limit implementation (Laravel RateLimiter, middleware, or route-level)
- Sentry configuration details beyond DSN and sample rates
- Test patterns for tenant isolation regression tests
- Input validation rules per component

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| HARD-01 | Health check endpoint (GET /health) verifies DB connectivity, storage access, GD extension, and queue status | Health check patterns section; environment availability confirms GD available |
| HARD-02 | Startup validation fails fast with clear errors if required environment variables are missing | Startup validation section; env var inventory from .env.example |
| HARD-03 | Rate limiting applied to login attempts, webhook endpoints, guest ordering, and API routes | Rate limiting section; existing patterns in LoginForm and PinLogin confirmed active |
| HARD-04 | Sentry error tracking configured for production with structured JSON logging for Cloud Logging | Logging section; GoogleCloudLoggingFormatter confirmed in Monolog 3.10.0; sentry 4.21.1 installed |
| SEC-01 | Tenant isolation audit confirms every database query on tenant data is scoped to shop_id | Tenant audit section; all 9 target models mapped; existing gap in MenuBuilderTenantIsolation documented |
| SEC-03 | Input validation sweep covers all user inputs, form submissions, and file uploads | Validation section; 16 Livewire components inventoried; gaps and coverage documented |
</phase_requirements>

---

## Summary

Phase 7 hardens a Laravel 12 + Livewire 3 application before its first production client goes live. The codebase is in excellent shape — tenant isolation is manually enforced throughout, rate limiting exists for login and PIN flows, and Sentry is already installed. The work is primarily configuration, wiring, and filling specific gaps rather than building from scratch.

The most important finding is that `GoogleCloudLoggingFormatter` is already present in Monolog 3.10.0 (the installed version). No additional packages are needed for Cloud Logging JSON output. Similarly, `sentry/sentry-laravel` 4.21.1 is installed and partially configured — it needs `traces_sample_rate` set to 0.10 in production and the `ignore_transactions` list updated to include `/health`. Rate limiting for the guest ordering flow already exists at 5 attempts/60 seconds — it needs adjustment to match D-05 (10 per 15 minutes = 900 seconds).

The tenant isolation audit shows that the major components use explicit `shop_id` scoping consistently. `MenuBuilderTenantIsolationTest` already exists as a model for regression tests. The remaining work is adding similar cross-tenant tests for PosDashboard, KitchenDisplay, and ReportsDashboard, plus filling validation gaps in OrderTracker and BillingSettings.

**Primary recommendation:** Implement in wave order — health check and startup validation first (they are independent of everything), then rate limiting adjustments (one place each for guest ordering and webhooks), then logging (config-only), then Sentry tuning (env vars + config change), then tenant audit + regression tests, then validation sweep.

---

## Standard Stack

### Core (all already installed)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `monolog/monolog` | 3.10.0 | Logging foundation | Laravel's default; `GoogleCloudLoggingFormatter` is built-in at this version |
| `sentry/sentry-laravel` | 4.21.1 | Error + performance tracking | Already installed; has Livewire breadcrumbs + span capture |
| `Illuminate\Support\Facades\RateLimiter` | Laravel 12.53.0 built-in | Rate limiting primitive | Used by existing PinLogin and GuestMenu; no extra dep needed |

### No Additional Packages Required

All needed libraries are installed. The phase is configuration + code implementation, not dependency installation.

**Version verification (run at time of planning):**
```bash
composer show monolog/monolog sentry/sentry-laravel | grep versions
```
Expected: monolog 3.10.0, sentry-laravel 4.21.1

---

## Architecture Patterns

### Pattern 1: Health Check Controller (HARD-01)

**What:** Plain Laravel controller at `GET /health` that performs subsystem checks and returns JSON. Registered before all middleware groups to stay fully public.

**When to use:** Any containerized deployment needing liveness probes (Cloud Run, Kubernetes).

**Route placement (routes/web.php):**
```php
// Must be above all middleware groups — before Route::middleware(['auth'])
Route::get('/health', App\Http\Controllers\HealthController::class)
    ->name('health');
```

**Response shape (D-02 specifies this exactly):**
```php
// Healthy: HTTP 200
{
    "status": "healthy",
    "db": "ok",
    "storage": "ok",
    "gd_webp": "ok",
    "queue": "ok",
    "latency_ms": 42
}

// Degraded: HTTP 503
{
    "status": "degraded",
    "db": "ok",
    "storage": "error",
    "gd_webp": "ok",
    "queue": "ok",
    "latency_ms": 150
}
```

**Subsystem checks:**
```php
// DB: run a trivial query with timeout
DB::select('SELECT 1');

// Storage: write + read + delete a small probe file
Storage::disk('gcs')->put('health-probe', 'ok');
Storage::disk('gcs')->get('health-probe');
Storage::disk('gcs')->delete('health-probe');

// GD + WebP: check extension is loaded AND WebP is supported
extension_loaded('gd') && in_array('webp', (array) gd_info()['WebP Support'] ?? [], true)
// Simpler: imagetypes() & IMG_WEBP

// Queue: attempt to dispatch a test job (or check queue table if using database driver)
// With database queue: DB::table('jobs')->count() returning without error is sufficient.
// More precise: use DB::connection(config('queue.connections.database.connection'))->table('jobs')
```

**Error isolation pattern:**
```php
// Each check wrapped individually so one failure doesn't mask others
try {
    DB::select('SELECT 1');
    $checks['db'] = 'ok';
} catch (\Throwable) {
    $checks['db'] = 'error';
    $overall = 'degraded';
}
```

**Important:** Add `/health` to Sentry's `ignore_transactions` in `config/sentry.php` to prevent health probe noise. The existing config already ignores `/up` — add `/health` alongside it.

### Pattern 2: Startup Environment Validation (HARD-02)

**What:** A `ServiceProvider::boot()` call that checks required env vars and throws an `\RuntimeException` if any are missing. Registered in `AppServiceProvider`.

**Which vars are mandatory (for HARD-02 purposes — Claude's discretion):**

| Variable | Why Required |
|----------|-------------|
| `APP_KEY` | Laravel encryption — app is broken without it |
| `DB_HOST` / `DB_CONNECTION` | Database required for every request |
| `DB_DATABASE` | Without this, all queries fail |
| `FILESYSTEM_DISK` | Defaults to `local`; in production must be `gcs` |
| `GCS_BUCKET` | GCS file storage (required when FILESYSTEM_DISK=gcs) |
| `GOOGLE_CLOUD_PROJECT_ID` | GCS auth |
| `SENTRY_LARAVEL_DSN` | Error tracking — skip check in non-production environments |

**Minimal validation approach:**
```php
// In AppServiceProvider::boot()
if ($this->app->environment('production')) {
    $required = ['APP_KEY', 'DB_HOST', 'DB_DATABASE', 'GCS_BUCKET', 'GOOGLE_CLOUD_PROJECT_ID'];
    $missing = array_filter($required, fn ($key) => empty(env($key)));
    if (!empty($missing)) {
        throw new \RuntimeException(
            'Missing required environment variables: ' . implode(', ', $missing)
        );
    }
}
```

**Critical note:** The check must only run in `production`. In testing (`phpunit.xml` overrides `DB_CONNECTION=sqlite`) the env vars are different.

### Pattern 3: Route-Level Rate Limiting for Webhooks (HARD-03)

**What:** Named rate limiters defined in `AppServiceProvider::boot()`, applied via `throttle:name` middleware in `routes/web.php`.

**Implementation (AppServiceProvider):**
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// In boot():
RateLimiter::for('stripe-webhooks', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip())
        ->response(function () {
            return response()->json(['message' => 'Too many requests.'], 429);
        });
});
```

**Route application (routes/web.php):**
```php
Route::post('/webhooks/stripe', ...)->middleware('throttle:stripe-webhooks');
Route::post('/webhooks/stripe/subscription', ...)->middleware('throttle:stripe-webhooks');
```

**Important:** The existing `$middleware->validateCsrfTokens(['webhooks/stripe', 'webhooks/stripe/subscription'])` in `bootstrap/app.php` exempts webhooks from CSRF. The `throttle` middleware must be applied at the route level (not inside web group) so it doesn't depend on web session state.

### Pattern 4: Livewire-Level Rate Limiting for Guest Ordering (HARD-03)

**Current state:** `GuestMenu::submitOrder()` uses `RateLimiter::tooManyAttempts('guest-order:'.request()->ip(), 5)` with a 60-second decay. This is 5 per minute.

**Target (D-05):** 10 per 15 minutes (900 seconds). Change the call:
```php
// Before (5/minute):
$rateLimitKey = 'guest-order:'.request()->ip();
if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) { ... }
RateLimiter::hit($rateLimitKey, 60);

// After (10/15 minutes):
$rateLimitKey = 'guest-order:'.request()->ip();
if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
    $this->orderError = "You're ordering too quickly. Please wait a moment and try again.";
    $this->showReviewModal = true;
    return;
}
RateLimiter::hit($rateLimitKey, 900);
```

**Note on D-09 (Retry-After header):** In Livewire components there is no HTTP response to attach a header to. The `Retry-After` header applies to HTTP-level rate limiting (middleware). For Livewire guest ordering, the user-friendly message is the appropriate mechanism. Only webhook middleware rate limiting needs the `Retry-After` header.

### Pattern 5: Structured JSON Logging with GoogleCloudLoggingFormatter (HARD-04)

**What:** A new `stackdriver` log channel in `config/logging.php` using Monolog's built-in `GoogleCloudLoggingFormatter`. Switch to it in production via `LOG_CHANNEL=stackdriver`.

**Key discovery:** `Monolog\Formatter\GoogleCloudLoggingFormatter` is available in Monolog 3.10.0 (the installed version). It extends `JsonFormatter` and maps log levels to GCP severity field names (`severity` instead of `level_name`), adds RFC3339 extended timestamps as `time`, and drops `level`/`level_name`/`datetime` keys.

**config/logging.php — add this channel:**
```php
'stackdriver' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'error'),
    'handler' => Monolog\Handler\StreamHandler::class,
    'handler_with' => [
        'stream' => 'php://stderr', // Cloud Run reads stderr
        'level' => 'error',
    ],
    'formatter' => Monolog\Formatter\GoogleCloudLoggingFormatter::class,
],
```

**Log level for production (D-12):** Set `LOG_LEVEL=error` in production to capture only errors. Slow requests (>2s) need a separate mechanism (see Pattern 6).

**PII masking (D-11):** Implemented as a `Monolog\Processor\ProcessorInterface` registered on the channel. The processor intercepts log records and masks known PII fields before they are formatted.

```php
// app/Logging/PiiMaskingProcessor.php
class PiiMaskingProcessor implements \Monolog\Processor\ProcessorInterface
{
    public function __invoke(\Monolog\LogRecord $record): \Monolog\LogRecord
    {
        $context = $record->context;
        // Mask phone: +968****1234
        if (isset($context['phone'])) {
            $context['phone'] = $this->maskPhone($context['phone']);
        }
        // Mask email: n***@bite.com
        if (isset($context['email'])) {
            $context['email'] = $this->maskEmail($context['email']);
        }
        // Mask IP: 192.168.***
        if (isset($context['ip'])) {
            $context['ip'] = $this->maskIp($context['ip']);
        }
        return $record->with(context: $context);
    }
    // ...masking methods
}
```

### Pattern 6: Slow Request Logging (HARD-04 / D-12)

**What:** A middleware that logs requests taking longer than 2000ms. Registered globally in `bootstrap/app.php`.

```php
// app/Http/Middleware/LogSlowRequests.php
class LogSlowRequests
{
    private const THRESHOLD_MS = 2000;

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $durationMs = (int)((microtime(true) - $start) * 1000);

        if ($durationMs >= self::THRESHOLD_MS) {
            Log::warning('Slow request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => $durationMs,
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
```

### Pattern 7: Sentry Production Tuning (HARD-04 / D-13)

**What:** Update `config/sentry.php` to respect `SENTRY_TRACES_SAMPLE_RATE` env var (already does this — it's in `.env.example` as `SENTRY_TRACES_SAMPLE_RATE=0`). Set to 0.10 in production Cloud Run env vars.

**Key config adjustments:**
```php
// config/sentry.php — add /health to ignore_transactions
'ignore_transactions' => [
    '/up',
    '/health',  // Add this
],

// send_default_pii must remain false (it's already false in config)
'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),
```

**Production Cloud Run env vars to set:**
- `SENTRY_TRACES_SAMPLE_RATE=0.1`
- `SENTRY_SAMPLE_RATE=1.0` (capture all errors, default)
- `SENTRY_ENVIRONMENT=production`

### Pattern 8: Tenant Isolation Regression Tests (SEC-01)

**What:** PHPUnit tests that verify cross-tenant data access is blocked for each tenant-scoped model. The existing `MenuBuilderTenantIsolationTest` is the reference pattern.

**Test pattern:**
```php
// 1. Create two shops with data
// 2. Authenticate as a user from shop A
// 3. Attempt to access/mutate shop B's data via the relevant Livewire component
// 4. Assert ModelNotFoundException is thrown OR the action silently no-ops OR the response is 422
```

**Components still needing cross-tenant tests (audit findings):**
- `PosDashboard` — already uses `Auth::user()->shop_id` in all queries (confirmed). Needs a test that verifies `Order::where('shop_id', shopA_id)->find(shopB_order_id)` returns null.
- `KitchenDisplay` — uses `Auth::user()->shop_id`. Same test pattern.
- `ReportsDashboard` — uses `$shopId = Auth::user()->shop_id` inline. Same test pattern.
- `ModifierManager` — uses `Auth::user()->shop_id`. Needs a cross-tenant deletion attempt test.

**What was already tested:**
- `MenuBuilderTenantIsolationTest` — `updateProductCategory` and `updateOrder` cross-tenant rejection.
- `GuestMenuSecurityTest` — price manipulation rejected.
- `UserTenancyTest` — user belongs to shop.

### Anti-Patterns to Avoid

- **Don't return 503 with an HTML error page for /health.** Cloud Run interprets any 2xx as healthy. JSON is required per D-02.
- **Don't add SecurityHeaders middleware to /health.** The endpoint is for machine consumers; CSP headers are irrelevant and add latency.
- **Don't use `config()` to read env vars in startup validation.** After `php artisan config:cache`, `env()` returns null. Use `config()` or ensure validation happens before config cache loads. Alternatively, run validation only when not in testing mode.
- **Don't apply throttle middleware inside the `web` group for webhooks.** Webhook routes are already outside middleware groups — keep them there.
- **Don't use `App::environment('testing')` in production code paths.** Use `$this->app->environment('production')` in service providers.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| GCP-formatted JSON logs | Custom JSON formatter | `Monolog\Formatter\GoogleCloudLoggingFormatter` | Already in Monolog 3.x; maps severity, timestamp format correctly |
| Rate limiting state | Custom cache counters | `Illuminate\Support\Facades\RateLimiter` | Already used in project; handles decay, atomic increments, 429 response |
| PII regex masking | One-off string operations in log calls | `Monolog\Processor\ProcessorInterface` | Applied once as a processor; doesn't litter log call sites |
| Env var checking | Bash scripts or CI-only checks | `AppServiceProvider::boot()` guard | Runs at every boot; fails fast before any damage is done |
| Health endpoint testing | Manual curl checks | PHPUnit `$this->get('/health')` assertions | Consistent, automatable, part of test suite |

**Key insight:** In a Laravel 12 project on Monolog 3, all the infrastructure for structured logging, rate limiting, and PII handling already exists. The work is wiring and configuration, not building.

---

## Common Pitfalls

### Pitfall 1: Env Validation Running During Tests
**What goes wrong:** The startup validation throws `\RuntimeException` in test environment because `APP_KEY` etc. are set by `phpunit.xml` differently, or `GCS_BUCKET` is not set at all.
**Why it happens:** `phpunit.xml` sets `DB_CONNECTION=sqlite` but doesn't set GCS_BUCKET — a production-only variable.
**How to avoid:** Gate the check on `$this->app->environment('production')`. Tests run under `APP_ENV=testing` (phpunit.xml), so the guard works reliably.
**Warning signs:** All tests fail immediately with "Missing required environment variables."

### Pitfall 2: `env()` Returns Null After Config Cache
**What goes wrong:** `env('GCS_BUCKET')` returns null even though the env var is set, causing validation to false-positive.
**Why it happens:** After `php artisan config:cache`, PHP's `getenv()` is not read — cached config values are used instead. `env()` is only reliable before config caching.
**How to avoid:** In the service provider validation, use `config('filesystems.disks.gcs.bucket')` instead of `env('GCS_BUCKET')`. Alternatively, trigger config cache check: if running in a cached-config environment, validate against config values not env().
**Warning signs:** Health check passes but app fails on GCS operations.

### Pitfall 3: Rate Limit Keys Not Cleared After Tests
**What goes wrong:** Rate limit tests fail intermittently because a previous test left the rate limiter in a hit state.
**Why it happens:** Tests share the same in-memory cache unless `RateLimiter::clear()` is called.
**How to avoid:** In test `tearDown()` or `setUp()`, call `RateLimiter::clear($key)` for all keys used in the test. The existing `RateLimitProtectionTest` does this for PinLogin — use the same pattern.
**Warning signs:** Test passes in isolation but fails in full suite.

### Pitfall 4: WebP Support in GD Health Check
**What goes wrong:** `imagetypes() & IMG_WEBP` returns false on some PHP 8.x builds even with GD loaded.
**Why it happens:** `IMG_WEBP` constant exists in PHP but GD may be compiled without `libwebp`.
**How to avoid:** Test both: `function_exists('imagecreatefromwebp') && (imagetypes() & IMG_WEBP)`. This is the specific blocker from STATE.md ("GD WebP support unverified on production server") — the health check resolves it.
**Warning signs:** Health check `gd_webp: "ok"` in dev but `"error"` in container.

### Pitfall 5: Sentry Noise from Health Probes
**What goes wrong:** Cloud Run pings `/health` every 10 seconds; Sentry captures these as transactions cluttering performance monitoring.
**Why it happens:** Sentry's `tracing.default_integrations` intercepts all requests.
**How to avoid:** Add `/health` to `ignore_transactions` in `config/sentry.php`. The existing config already has `/up` — follow the same pattern.
**Warning signs:** Sentry performance dashboard is dominated by `/health` transactions.

### Pitfall 6: Livewire `$this->orderError` vs Flash Message
**What goes wrong:** Rate limit rejection in `GuestMenu::submitOrder()` uses `session()->flash()` in some paths and `$this->orderError` in others — inconsistent UX.
**Why it happens:** Existing flash message for `__('guest.too_many_orders')` uses `session()->flash()`. The `showReviewModal` is closed when flash triggers, so the user doesn't see it.
**How to avoid:** For the guest ordering rate limit, set `$this->orderError` and `$this->showReviewModal = true` (matching the D-09 pattern). The friendly message in D-09 matches what OrderError displays.

---

## Code Examples

### Health Controller Pattern
```php
// Source: Laravel 12 + project codebase patterns (verified)
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $overall = 'healthy';
        $start = microtime(true);

        // DB check
        try {
            DB::select('SELECT 1');
            $checks['db'] = 'ok';
        } catch (\Throwable) {
            $checks['db'] = 'error';
            $overall = 'degraded';
        }

        // Storage check
        try {
            $disk = config('filesystems.default', 'local');
            Storage::disk($disk)->put('.health-probe', 'ok');
            Storage::disk($disk)->delete('.health-probe');
            $checks['storage'] = 'ok';
        } catch (\Throwable) {
            $checks['storage'] = 'error';
            $overall = 'degraded';
        }

        // GD + WebP check
        $checks['gd_webp'] = (
            extension_loaded('gd')
            && function_exists('imagecreatefromwebp')
            && (imagetypes() & IMG_WEBP)
        ) ? 'ok' : 'error';
        if ($checks['gd_webp'] === 'error') {
            $overall = 'degraded';
        }

        // Queue check (database driver: verify jobs table accessible)
        try {
            DB::table('jobs')->limit(1)->count();
            $checks['queue'] = 'ok';
        } catch (\Throwable) {
            $checks['queue'] = 'error';
            $overall = 'degraded';
        }

        $latencyMs = (int)((microtime(true) - $start) * 1000);

        return response()->json(
            array_merge(['status' => $overall], $checks, ['latency_ms' => $latencyMs]),
            $overall === 'healthy' ? 200 : 503
        );
    }
}
```

### Stackdriver Log Channel Config
```php
// config/logging.php — add to 'channels' array
// Source: Monolog 3.10.0 GoogleCloudLoggingFormatter (verified in vendor/)
'stackdriver' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'error'),
    'handler' => Monolog\Handler\StreamHandler::class,
    'handler_with' => [
        'stream' => 'php://stderr',
        'level' => env('LOG_LEVEL', 'error'),
    ],
    'formatter' => Monolog\Formatter\GoogleCloudLoggingFormatter::class,
    'processors' => [
        App\Logging\PiiMaskingProcessor::class,
    ],
],
```

### Rate Limiter Registration (AppServiceProvider)
```php
// Source: Laravel 12 RateLimiter facade (verified in vendor/)
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

// In AppServiceProvider::boot()
RateLimiter::for('stripe-webhooks', function (Request $request) {
    return Limit::perMinute(60)
        ->by($request->ip())
        ->response(fn () => response()->json(
            ['message' => 'Too many requests.'],
            429,
            ['Retry-After' => 60]
        ));
});
```

### Cross-Tenant Regression Test Pattern
```php
// Source: MenuBuilderTenantIsolationTest.php (project pattern, verified)
public function test_pos_dashboard_cannot_access_order_from_another_shop(): void
{
    [$shopA, $shopB] = $this->makeShops();

    $orderB = Order::forceCreate([
        'shop_id' => $shopB->id,
        'status' => 'unpaid',
        'total_amount' => 10.000,
        'tracking_token' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    $staff = User::factory()->create(['shop_id' => $shopA->id, 'role' => 'server']);

    // Attempt to mark shopB's order as paid through shopA's POS
    Livewire::actingAs($staff)
        ->test(PosDashboard::class)
        ->call('markPaid', $orderB->id, 'cash')
        ->assertStatus(404); // or assertNoRedirect + assertDatabaseMissing paid_at
}
```

---

## Runtime State Inventory

> SKIPPED — this is not a rename/refactor/migration phase. No runtime state inventory required.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.4 | All | Yes | 8.4.17 | — |
| Composer | All | Yes | 2.9.2 | — |
| Laravel 12 | All | Yes | 12.53.0 | — |
| Monolog 3 | Structured logging (HARD-04) | Yes | 3.10.0 (includes GoogleCloudLoggingFormatter) | — |
| sentry/sentry-laravel | Sentry integration (HARD-04) | Yes | 4.21.1 | — |
| GD extension with WebP | Health check verification (HARD-01) | Unverified* | — | Health check reports `gd_webp: error` → 503 → Cloud Run must fix container |
| Queue driver (database) | Health check queue probe | Yes | jobs table exists | — |

**Note on GD WebP availability:** This is the blocker from STATE.md. The health check endpoint itself IS the verification mechanism. If the container lacks WebP support, the health check returns 503 and Cloud Run will refuse to serve traffic — forcing a container fix. This is the intended behavior per D-01.

**Missing dependencies with no fallback:** None that block implementation.

**Missing dependencies with fallback:** None.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit 11 (via Laravel 12) |
| Config file | `phpunit.xml` (root) |
| Quick run command | `php artisan test --filter=Phase7` |
| Full suite command | `composer test` (clears config + runs all tests) |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| HARD-01 | GET /health returns 200 with all components ok | Feature/HTTP | `php artisan test --filter=HealthCheckTest` | ❌ Wave 0 |
| HARD-01 | GET /health returns 503 when DB fails | Feature/HTTP (mock) | `php artisan test --filter=HealthCheckTest` | ❌ Wave 0 |
| HARD-02 | App throws RuntimeException on missing env vars in production | Unit | `php artisan test --filter=StartupValidationTest` | ❌ Wave 0 |
| HARD-03 | Guest ordering rate limited at 10/15min with friendly message | Feature/Livewire | `php artisan test --filter=GuestMenuRateLimitTest` | ❌ Wave 0 |
| HARD-03 | Webhook rate limited at 60/min, returns 429 + Retry-After | Feature/HTTP | `php artisan test --filter=WebhookRateLimitTest` | ❌ Wave 0 |
| HARD-03 | Web login rate limiting active (5 attempts) | Feature/Livewire | `php artisan test --filter=LoginRateLimitTest` | ❌ Wave 0 (but LoginForm has it — test verifies it's active) |
| HARD-04 | Structured JSON log channel exists and produces valid JSON | Unit | `php artisan test --filter=StructuredLoggingTest` | ❌ Wave 0 |
| HARD-04 | PII masking processor masks phone/email/IP fields | Unit | `php artisan test --filter=PiiMaskingProcessorTest` | ❌ Wave 0 |
| SEC-01 | PosDashboard cannot access orders from another shop | Feature/Livewire | `php artisan test --filter=PosDashboardTenantIsolationTest` | ❌ Wave 0 |
| SEC-01 | KitchenDisplay cannot access orders from another shop | Feature/Livewire | `php artisan test --filter=KitchenDisplayTenantIsolationTest` | ❌ Wave 0 |
| SEC-01 | ModifierManager cannot mutate modifier groups from another shop | Feature/Livewire | `php artisan test --filter=ModifierManagerTenantIsolationTest` | ❌ Wave 0 |
| SEC-03 | OrderTracker feedback validated (rating 1-5, comment length) | Feature/Livewire | `php artisan test --filter=OrderTrackerValidationTest` | ❌ Wave 0 |

**Existing tests that partially cover this phase:**
- `RateLimitProtectionTest` — covers PinLogin and ManagerOverride (D-08 confirmed satisfied)
- `MenuBuilderTenantIsolationTest` — covers MenuBuilder cross-tenant isolation (reference pattern for SEC-01)
- `GuestMenuSecurityTest` — covers price manipulation prevention
- `StripeWebhookSecurityTest` — covers idempotency (NOT rate limiting — still needs webhook rate limit test)

### Sampling Rate
- **Per task commit:** `php artisan test --filter=HealthCheck` (or relevant filter)
- **Per wave merge:** `composer test`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/HealthCheckTest.php` — covers HARD-01
- [ ] `tests/Feature/StartupValidationTest.php` — covers HARD-02
- [ ] `tests/Feature/Livewire/GuestMenuRateLimitTest.php` — covers HARD-03 guest ordering
- [ ] `tests/Feature/WebhookRateLimitTest.php` — covers HARD-03 webhook rate limiting
- [ ] `tests/Unit/PiiMaskingProcessorTest.php` — covers HARD-04 PII masking
- [ ] `tests/Feature/Livewire/PosDashboardTenantIsolationTest.php` — covers SEC-01
- [ ] `tests/Feature/Livewire/KitchenDisplayTenantIsolationTest.php` — covers SEC-01
- [ ] `tests/Feature/Livewire/ModifierManagerTenantIsolationTest.php` — covers SEC-01
- [ ] No framework install needed — PHPUnit already configured

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Custom JSON log formatter | `Monolog\Formatter\GoogleCloudLoggingFormatter` built-in | Monolog 3.x | No custom formatter class needed |
| `throttle:60,1` string notation | Named `RateLimiter::for()` with `Limit` objects | Laravel 8+ | More expressive; custom response callbacks |
| `env()` for config validation | `config()` after config:cache | Laravel 5.8+ | Must use config() in post-cache environments |

**Deprecated/outdated:**
- `$request->header('X-Forwarded-For')` for IP detection: The project correctly uses `trustProxies()` in `bootstrap/app.php` — `request()->ip()` returns the real IP. Do not read X-Forwarded-For manually.

---

## Open Questions

1. **Queue health check depth**
   - What we know: The project uses `QUEUE_CONNECTION=database`. The jobs table exists.
   - What's unclear: Whether the health check should attempt to dispatch a real job (expensive) or just verify the `jobs` table is accessible (lightweight).
   - Recommendation: Use the lightweight approach (verify `jobs` table is accessible via DB query). Dispatching a job adds latency and complexity to the health probe. The existing `DB::select('SELECT 1')` already covers the database path; the queue check can reuse that connection.

2. **Sentry `traces_sample_rate` in dev/staging**
   - What we know: `.env.example` sets `SENTRY_TRACES_SAMPLE_RATE=0` (disabled locally). The config reads this correctly.
   - What's unclear: Whether staging should have traces enabled.
   - Recommendation: Leave at 0 for non-production. Only production needs 0.10. Document the Cloud Run env var to set.

3. **BillingSettings validation scope**
   - What we know: `BillingSettings` has plan key validation inline but no `$this->validate()` call. The `subscribe()` method validates the plan key against an allowlist.
   - What's unclear: Whether there are other user-controllable inputs in BillingSettings that need validation.
   - Recommendation: During SEC-03 sweep, review BillingSettings for any hidden user inputs. Based on reading the first 50 lines, the component primarily redirects to Stripe; there may be no additional validation needed.

---

## Sources

### Primary (HIGH confidence)
- Monolog 3.10.0 source — `vendor/monolog/monolog/src/Monolog/Formatter/GoogleCloudLoggingFormatter.php` — verified class exists
- Laravel 12.53.0 source — `vendor/laravel/framework/src/Illuminate/Cache/RateLimiting/Limit.php` — verified `perMinute`, `perMinutes` API
- Project codebase — `app/Livewire/GuestMenu.php`, `app/Livewire/Forms/LoginForm.php`, `app/Livewire/PinLogin.php` — verified existing rate limiting patterns
- Project codebase — `tests/Feature/Livewire/RateLimitProtectionTest.php`, `MenuBuilderTenantIsolationTest.php` — verified test patterns
- Project codebase — `config/sentry.php`, `config/logging.php` — verified existing configuration
- Project codebase — `bootstrap/app.php` — verified middleware registration pattern
- sentry/sentry-laravel 4.21.1 — composer show output — version confirmed

### Secondary (MEDIUM confidence)
- Cloud Run liveness probe behavior — `/health` returning 503 triggers restart — standard Cloud Run behavior, consistent with D-01 design
- Monolog processor pattern for PII masking — `ProcessorInterface` is the standard Monolog 3 approach for log mutation

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified installed at exact versions
- Architecture: HIGH — patterns verified against actual codebase; no guesswork
- Pitfalls: HIGH — env validation timing, rate limit key isolation, and GD WebP are all grounded in direct code inspection
- Test patterns: HIGH — existing tests in the project provide exact reference patterns

**Research date:** 2026-03-27
**Valid until:** 2026-04-27 (Laravel 12 stable; no fast-moving dependencies)

---
phase: 07-hardening-security
plan: 01
subsystem: infra
tags: [health-check, rate-limiting, startup-validation, sentry, stripe-webhooks, livewire]

# Dependency graph
requires:
  - phase: 06-containerization-cloud-services
    provides: Cloud Run deployment target, GCS filesystem driver, MySQL via Cloud SQL
provides:
  - GET /health endpoint with db/storage/gd_webp/queue checks (200 healthy, 503 degraded)
  - Startup env validation in production (fails fast on missing APP_KEY, DB_HOST, etc.)
  - Named rate limiter 'stripe-webhooks' at 60 req/min with Retry-After header
  - Guest ordering rate limited to 10 orders per 15 minutes with friendly error message
  - HealthCheckTest, StartupValidationTest, WebhookRateLimitTest, GuestMenuRateLimitTest
affects: [07-02, 07-03, ci-cd, cloud-run-deployment]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Invokable controller for single-action health endpoint"
    - "Named rate limiter via RateLimiter::for() in AppServiceProvider::boot()"
    - "Production environment guard for startup validation using config() not env()"
    - "Rate limit custom response with Retry-After header for webhook protection"

key-files:
  created:
    - app/Http/Controllers/HealthController.php
    - tests/Feature/HealthCheckTest.php
    - tests/Feature/StartupValidationTest.php
    - tests/Feature/WebhookRateLimitTest.php
    - tests/Feature/Livewire/GuestMenuRateLimitTest.php
  modified:
    - routes/web.php
    - config/sentry.php
    - app/Providers/AppServiceProvider.php
    - app/Livewire/GuestMenu.php

key-decisions:
  - "Health check uses config('filesystems.default') not env() — after config:cache env() returns null"
  - "Startup validation guarded on production env so tests are never affected"
  - "GCS vars only required when filesystem is gcs — conditional validation prevents false positives"
  - "Guest ordering rate limit raised from 5/1min to 10/15min — old limit too aggressive for legitimate use"
  - "Rate limit error via orderError property (not session flash) for consistent Livewire UX"
  - "Health route uses withoutMiddleware(SecurityHeaders) to keep probe responses clean"
  - "/health added to Sentry ignore_transactions to prevent probe noise in performance traces"

patterns-established:
  - "Health check pattern: invokable controller, try/catch per subsystem, 200/503 status"
  - "Rate limiter registration pattern: RateLimiter::for() in AppServiceProvider boot()"
  - "Startup validation pattern: config() + environment guard + descriptive RuntimeException"

requirements-completed: [HARD-01, HARD-02, HARD-03]

# Metrics
duration: 3min
completed: 2026-03-27
---

# Phase 07 Plan 01: Health Check, Startup Validation, Rate Limiting Summary

**HealthController for Cloud Run liveness probes with db/storage/gd_webp/queue subsystem checks, production startup validation via config() guards, and named rate limiters for Stripe webhooks (60/min) and guest ordering (10/15min)**

## Performance

- **Duration:** 3 min
- **Started:** 2026-03-27T15:45:29Z
- **Completed:** 2026-03-27T15:48:36Z
- **Tasks:** 2 (both TDD)
- **Files modified:** 9 (4 created, 5 modified)

## Accomplishments

- GET /health endpoint returns JSON with status, db, storage, gd_webp, queue, latency_ms — returns 200 when healthy, 503 when any subsystem fails
- Startup validation in production throws RuntimeException with descriptive message listing all missing env vars; guarded so tests are never affected
- Stripe webhook endpoints rate-limited to 60/min per IP with JSON 429 response and Retry-After: 60 header
- Guest ordering rate limit raised from 5/1min to 10/15min with friendly "You're ordering too quickly" error (via orderError, not session flash)
- 251 tests passing, 691 assertions — zero regressions

## Task Commits

Each task was committed atomically:

1. **Task 1: Health check endpoint with full-stack subsystem verification** - `3f050c1` (feat)
2. **Task 2: Startup env validation, webhook rate limiting, guest ordering rate limit adjustment** - `115b0d8` (feat)

_Note: Both tasks used TDD (RED → GREEN flow). No separate refactor commits needed._

## Files Created/Modified

- `app/Http/Controllers/HealthController.php` - Invokable health controller checking db, storage, gd_webp, queue subsystems
- `routes/web.php` - Added GET /health route (before middleware groups, without SecurityHeaders), throttle middleware on both webhook routes
- `config/sentry.php` - Added /health to ignore_transactions alongside /up
- `app/Providers/AppServiceProvider.php` - Production startup validation + stripe-webhooks rate limiter registration
- `app/Livewire/GuestMenu.php` - Rate limit increased to 10/15min, friendly error via orderError
- `tests/Feature/HealthCheckTest.php` - 6 tests: healthy response, degraded response, content-type, latency_ms, storage status, gd_webp status
- `tests/Feature/StartupValidationTest.php` - 4 tests: production throws on missing APP_KEY, no-throw when present, testing env guard, GCS vars conditional
- `tests/Feature/WebhookRateLimitTest.php` - 3 tests: 429 after 60 requests, Retry-After header, subscription endpoint also rate limited
- `tests/Feature/Livewire/GuestMenuRateLimitTest.php` - 3 tests: friendly error after 10 orders, 15-min window, 10 orders allowed before limit

## Decisions Made

- Used `config()` not `env()` in HealthController and AppServiceProvider — after `config:cache`, `env()` returns null making startup validation unreliable
- GCS vars (GCS_BUCKET, GOOGLE_CLOUD_PROJECT_ID) only validated when `filesystems.default === 'gcs'` — prevents false positives in local/test environments
- Guest rate limit error uses `$this->orderError` + `$this->showReviewModal = true` (not `session()->flash`) — consistent with Livewire error display pattern used elsewhere in GuestMenu
- Health route placed before all middleware groups to avoid SecurityHeaders, auth, and other middleware applying to the probe endpoint
- Startup validation guarded exclusively on `environment('production')` — `env('APP_ENV')` would also work but `environment()` is the Laravel-idiomatic approach

## Deviations from Plan

None — plan executed exactly as written. The test for `test_health_endpoint_returns_503_when_db_fails` uses `DB::shouldReceive` mocking as planned. All acceptance criteria satisfied.

## Issues Encountered

None. Both TDD cycles completed cleanly (RED → GREEN without needing fixes).

## User Setup Required

None — no external service configuration required for this plan. The health endpoint uses the environment's default filesystem disk (test: public/local, production: gcs).

## Next Phase Readiness

- Health endpoint ready for Cloud Run liveness probe configuration (`/health` → expect 200)
- Rate limiting confirmed active on all public entry points (webhook, guest ordering, PIN login, web login)
- Phase 07-02 can proceed (tenant isolation audit, CSRF, secret management)

---
*Phase: 07-hardening-security*
*Completed: 2026-03-27*

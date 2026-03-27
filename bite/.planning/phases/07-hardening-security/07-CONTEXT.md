# Phase 7: Hardening & Security - Context

**Gathered:** 2026-03-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Production-harden Bite-POS with a comprehensive health check endpoint, rate limiting across all entry points, structured JSON logging with PII masking, Sentry error/performance tracking, a tenant isolation audit with regression tests, and an input validation sweep across all Livewire components. The app must be production-safe before Sourdough Oman goes live.

</domain>

<decisions>
## Implementation Decisions

### Health Check Endpoint (HARD-01)
- **D-01:** GET /health performs a **full stack check** — verifies DB connectivity, GCS storage access, GD extension with WebP support, and queue connection. If any check fails, return HTTP 503 (Cloud Run restarts the container).
- **D-02:** Response is **detailed JSON** with status per component: `{"status":"healthy","db":"ok","storage":"ok","gd_webp":"ok","queue":"ok","latency_ms":42}`. Same format in all environments.
- **D-03:** Endpoint is **public, no authentication required**. Cloud Run liveness probes cannot pass auth headers. Detailed status is operational data, not secrets.

### Startup Environment Validation (HARD-02)
- **D-04:** App refuses to boot and logs a clear error message when any required environment variable is missing. Fail-fast behavior — Claude has discretion on which env vars are mandatory and the validation approach.

### Rate Limiting (HARD-03)
- **D-05:** Guest ordering: **~10 orders per 15 minutes per IP**. Moderate threshold that handles groups ordering from the same WiFi without blocking legitimate use.
- **D-06:** Webhook endpoints (Stripe): **~60 requests/minute per IP**. High threshold — Stripe sends bursts during subscription events. Idempotency via `webhook_events` table already prevents duplicate processing.
- **D-07:** Web login (Breeze email/password): **Use Laravel's built-in rate limiting** (5 attempts/minute per email+IP). Verify it's active and test it — no custom overrides needed.
- **D-08:** PIN login and manager override: **Already rate-limited** (5 attempts per shop+IP). Existing implementation is tested and sufficient.
- **D-09:** 429 response for guest-facing endpoints: **Friendly user message** — "You're ordering too quickly. Please wait a moment and try again." Include `Retry-After` header for programmatic clients.

### Logging & Observability (HARD-04)
- **D-10:** Production logging uses **structured JSON format** for Cloud Logging queryability.
- **D-11:** PII handling: **Mask sensitive data** — phone numbers as `+968****1234`, emails as `n***@bite.com`, IPs as `192.168.***`. Enough to debug, not enough to expose customers.
- **D-12:** Request logging scope: **Errors + slow requests** (>2 seconds). Keep production logs lean — Sentry handles error tracking with full context.
- **D-13:** Sentry configured for **errors + sampled performance traces** (~10% sample rate). All errors captured, performance traces sampled to control cost.

### Tenant Isolation Audit (SEC-01)
- **D-14:** Audit is a **pre-deploy gate** — every gap found gets fixed in this phase. No runtime detection or middleware guard. The code itself must be correct.
- **D-15:** Audit includes **regression tests** that verify each tenant-scoped model's queries are scoped to `shop_id`. Future code changes breaking isolation will fail the test suite.

### Input Validation Sweep (SEC-03)
- **D-16:** Sweep covers **all Livewire components** (not just public-facing). Admin inputs can be stored XSS or injection vectors. All 6 components with existing validation get reviewed, plus any without validation get it added.

### Claude's Discretion
- Startup validation: which env vars are mandatory and validation implementation approach
- JSON logging format details (field names, nesting, timestamp format)
- PII masking implementation approach (middleware, log formatter, or processor)
- Health check timeout thresholds per subsystem
- Specific rate limit implementation (Laravel RateLimiter, middleware, or route-level)
- Sentry configuration details beyond DSN and sample rates
- Test patterns for tenant isolation regression tests
- Input validation rules per component (what constitutes sufficient validation)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Security & Middleware
- `app/Http/Middleware/SecurityHeaders.php` — Existing CSP, HSTS, X-Frame-Options, XSS protection headers
- `app/Http/Middleware/EnsureUserHasRole.php` — Role-based access control
- `app/Http/Middleware/EnsureUserIsSuperAdmin.php` — Super admin gate
- `app/Http/Middleware/CheckSubscription.php` — Subscription verification

### Rate Limiting (Existing)
- `app/Livewire/PinLogin.php` — PIN login rate limiting implementation (5 attempts per shop+IP)
- `app/Livewire/PosDashboard.php` — Manager override rate limiting
- `tests/Feature/Livewire/RateLimitProtectionTest.php` — Existing rate limit tests (reference pattern)

### Validation (Existing)
- `app/Livewire/OnboardingWizard.php` — Has validation (file uploads, Snap-to-Menu)
- `app/Livewire/ShopSettings.php` — Has validation
- `app/Livewire/ProductManager.php` — Has validation (product images, form data)
- `app/Livewire/ModifierManager.php` — Has validation
- `app/Livewire/ShiftReport.php` — Has validation
- `app/Livewire/CashReconciliation.php` — Has validation

### Tenant-Scoped Models (Audit Targets)
- `app/Models/Product.php` — shop_id scoped, guarded
- `app/Models/Order.php` — shop_id scoped, guarded
- `app/Models/User.php` — shop_id scoped, guarded
- `app/Models/Category.php` — shop_id scoped
- `app/Models/ModifierGroup.php` — shop_id scoped
- `app/Models/PricingRule.php` — shop_id scoped
- `app/Models/GroupCart.php` — shop_id scoped
- `app/Models/LoyaltyCustomer.php` — shop_id scoped
- `app/Models/AuditLog.php` — shop_id scoped

### Observability
- `config/sentry.php` — Existing Sentry config (DSN, sample rate, traces)
- `config/logging.php` — Current logging config (default stack, file-based)

### Webhooks (Rate Limit Targets)
- `routes/web.php` — All route definitions including webhook endpoints
- `app/Http/Controllers/StripeWebhookController.php` — Payment webhook (idempotent via webhook_events)
- `app/Http/Controllers/StripeSubscriptionWebhookController.php` — Subscription lifecycle webhook

### Requirements
- `.planning/REQUIREMENTS.md` — HARD-01, HARD-02, HARD-03, HARD-04, SEC-01, SEC-03 mapped to this phase

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `SecurityHeaders` middleware — Already handles CSP, HSTS, X-Frame-Options. Extend or supplement, don't replace.
- `RateLimiter` pattern in PinLogin/PosDashboard — Established pattern for Livewire-level rate limiting with `RateLimiter::tooManyAttempts()` / `RateLimiter::hit()`.
- `RateLimitProtectionTest` — Test pattern for rate limit verification. Reuse this approach for new rate limits.
- Sentry package (`sentry/sentry-laravel`) — Already installed and configured. Needs production tuning.

### Established Patterns
- Livewire validation — 6 components use `$this->validate()` with rule arrays. Same pattern for any missing validation.
- Manual `shop_id` scoping — No tenancy package. Each query must explicitly filter by `shop_id`. Models use `$guarded` to protect shop_id from mass assignment.
- Environment-based config — All config reads from env vars via `config/*.php`. Startup validation builds on this pattern.

### Integration Points
- `routes/web.php` — Add `/health` route, apply rate limiting middleware to webhook routes and guest ordering
- `config/logging.php` — Add JSON channel for production
- `config/sentry.php` — Set traces_sample_rate and production-specific settings
- `app/Providers/AppServiceProvider.php` — Register startup validation, configure rate limiters
- `bootstrap/app.php` — Middleware registration for rate limiting

</code_context>

<specifics>
## Specific Ideas

- GD WebP verification in health check resolves the Phase 6 blocker flagged in STATE.md
- Sourdough Oman is the first production client — the hardening must handle their 33-item bilingual menu with photos reliably
- Existing `webhook_events` idempotency table means webhook rate limiting is abuse prevention only, not correctness

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 07-hardening-security*
*Context gathered: 2026-03-27*

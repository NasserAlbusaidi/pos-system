---
phase: 7
slug: hardening-security
status: approved
nyquist_compliant: true
wave_0_complete: true
created: 2026-03-27
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.
> All plans use `tdd="true"` — tests are written inline within each task, not via a separate Wave 0 plan.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 11 (via Laravel 12) |
| **Config file** | `phpunit.xml` (root) |
| **Quick run command** | `php artisan test --filter=Phase7` |
| **Full suite command** | `composer test` (clears config + runs all tests) |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=<RelevantTest>`
- **After every plan wave:** Run `composer test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 07-01-01 | 01 | 1 | HARD-01 | Feature/HTTP | `php artisan test --filter=HealthCheckTest` | TDD inline | ⬜ pending |
| 07-01-02 | 01 | 1 | HARD-02, HARD-03 | Feature/HTTP+Livewire | `php artisan test --filter="StartupValidationTest\|WebhookRateLimitTest\|GuestMenuRateLimitTest"` | TDD inline | ⬜ pending |
| 07-02-01 | 02 | 1 | HARD-04 | Unit | `php artisan test --filter="PiiMaskingProcessorTest\|StructuredLoggingTest"` | TDD inline | ⬜ pending |
| 07-02-02 | 02 | 1 | HARD-04 | Feature | `php artisan test` | TDD inline | ⬜ pending |
| 07-03-01 | 03 | 1 | SEC-01 | Feature/Livewire | `php artisan test --filter="PosDashboardTenantIsolationTest\|KitchenDisplayTenantIsolationTest\|ModifierManagerTenantIsolationTest\|ReportsDashboardTenantIsolationTest"` | TDD inline | ⬜ pending |
| 07-03-02 | 03 | 1 | SEC-03 | Feature/Livewire | `php artisan test --filter="OrderTrackerValidationTest\|InputValidationSweepTest"` | TDD inline | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

*All plans use `tdd="true"` — tests are created within each task as the first step (RED phase). No separate Wave 0 plan is needed. Existing PHPUnit infrastructure is sufficient.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Sentry error appears in dashboard | HARD-04 | Requires external Sentry account | Throw test exception, verify in Sentry UI |
| Cloud Logging JSON queryability | HARD-04 | Requires deployed Cloud Run environment | Deploy, trigger error, query in Cloud Console |
| GCS storage probe in health check | HARD-01 | Tests use local disk; GCS requires deployed env | Deploy to Cloud Run, hit /health, verify storage status |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references (N/A — TDD inline)
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** approved 2026-03-27

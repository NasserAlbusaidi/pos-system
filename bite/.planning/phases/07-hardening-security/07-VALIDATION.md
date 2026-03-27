---
phase: 7
slug: hardening-security
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-27
---

# Phase 7 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

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
| 07-01-01 | 01 | 1 | HARD-01 | Feature/HTTP | `php artisan test --filter=HealthCheckTest` | ❌ W0 | ⬜ pending |
| 07-01-02 | 01 | 1 | HARD-01 | Feature/HTTP | `php artisan test --filter=HealthCheckTest` | ❌ W0 | ⬜ pending |
| 07-01-03 | 01 | 1 | HARD-02 | Unit | `php artisan test --filter=StartupValidationTest` | ❌ W0 | ⬜ pending |
| 07-01-04 | 01 | 1 | HARD-03 | Feature/Livewire | `php artisan test --filter=GuestMenuRateLimitTest` | ❌ W0 | ⬜ pending |
| 07-01-05 | 01 | 1 | HARD-03 | Feature/HTTP | `php artisan test --filter=WebhookRateLimitTest` | ❌ W0 | ⬜ pending |
| 07-01-06 | 01 | 1 | HARD-03 | Feature/Livewire | `php artisan test --filter=LoginRateLimitTest` | ❌ W0 | ⬜ pending |
| 07-02-01 | 02 | 2 | HARD-04 | Unit | `php artisan test --filter=StructuredLoggingTest` | ❌ W0 | ⬜ pending |
| 07-02-02 | 02 | 2 | HARD-04 | Unit | `php artisan test --filter=PiiMaskingProcessorTest` | ❌ W0 | ⬜ pending |
| 07-02-03 | 02 | 2 | SEC-01 | Feature/Livewire | `php artisan test --filter=PosDashboardTenantIsolationTest` | ❌ W0 | ⬜ pending |
| 07-02-04 | 02 | 2 | SEC-01 | Feature/Livewire | `php artisan test --filter=KitchenDisplayTenantIsolationTest` | ❌ W0 | ⬜ pending |
| 07-02-05 | 02 | 2 | SEC-01 | Feature/Livewire | `php artisan test --filter=ModifierManagerTenantIsolationTest` | ❌ W0 | ⬜ pending |
| 07-02-06 | 02 | 2 | SEC-03 | Feature/Livewire | `php artisan test --filter=OrderTrackerValidationTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/HealthCheckTest.php` — covers HARD-01
- [ ] `tests/Feature/StartupValidationTest.php` — covers HARD-02
- [ ] `tests/Feature/Livewire/GuestMenuRateLimitTest.php` — covers HARD-03 guest ordering
- [ ] `tests/Feature/WebhookRateLimitTest.php` — covers HARD-03 webhook rate limiting
- [ ] `tests/Unit/PiiMaskingProcessorTest.php` — covers HARD-04 PII masking
- [ ] `tests/Feature/Livewire/PosDashboardTenantIsolationTest.php` — covers SEC-01
- [ ] `tests/Feature/Livewire/KitchenDisplayTenantIsolationTest.php` — covers SEC-01
- [ ] `tests/Feature/Livewire/ModifierManagerTenantIsolationTest.php` — covers SEC-01

*Existing infrastructure covers framework — PHPUnit already configured.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Sentry error appears in dashboard | HARD-04 | Requires external Sentry account | Throw test exception, verify in Sentry UI |
| Cloud Logging JSON queryability | HARD-04 | Requires deployed Cloud Run environment | Deploy, trigger error, query in Cloud Console |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

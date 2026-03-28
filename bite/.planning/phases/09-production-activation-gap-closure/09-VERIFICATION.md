---
phase: 09-production-activation-gap-closure
verified: 2026-03-28T00:00:00Z
status: gaps_found
score: 5/6 must-haves verified
re_verification: false
gaps:
  - truth: "Cloud SQL automated daily backups are enabled with 7-day retention"
    status: failed
    reason: "GCP-imposed Free Trial instance restriction blocks backup configuration changes on the 'bite' Cloud SQL instance. enabled: false, binaryLogEnabled: false. This is a time-based GCP restriction that lifts automatically ~48-72 hours after instance creation (created 2026-03-27). Not a code defect — the retry command is documented."
    artifacts:
      - path: "GCP Cloud SQL instance: bite (ascent-web-260224-119)"
        issue: "backupConfiguration.enabled=false, binaryLogEnabled=false — GCP API returns HTTP 400: Free Trial Instance restriction blocks Backup Configuration and PITR"
    missing:
      - "Retry: gcloud sql instances patch bite --project=ascent-web-260224-119 --backup-start-time=02:00 --retained-backups-count=7 --enable-bin-log --retained-transaction-log-days=7 (on 2026-03-29 or later)"
  - truth: "Binary logging is enabled for point-in-time recovery"
    status: failed
    reason: "Same root cause as backup enablement — GCP Free Trial instance restriction. binaryLogEnabled: false in Cloud SQL instance metadata."
    artifacts:
      - path: "GCP Cloud SQL instance: bite (ascent-web-260224-119)"
        issue: "backupConfiguration.binaryLogEnabled=false — blocked by same GCP restriction"
    missing:
      - "Resolved by the same retry command as above (--enable-bin-log flag)"
human_verification:
  - test: "Verify Sentry is receiving real production exceptions"
    expected: "Trigger a 500 error on the live service and confirm it appears in the Sentry dashboard at o4511024693444608"
    why_human: "Cannot programmatically verify Sentry ingestion without generating a real exception and checking the dashboard"
  - test: "Verify GCS image uploads work end-to-end"
    expected: "Upload a product image in the admin UI; image URL should resolve to storage.googleapis.com/bite-pos-storage/..."
    why_human: "Cloud Run IAM (roles/storage.objectAdmin) for the service account cannot be verified with gcloud describe alone; actual upload test is definitive"
---

# Phase 9: Production Activation & Gap Closure — Verification Report

**Phase Goal:** All production services fully activated (database backups, structured logging, error tracking) and audit gaps from v1.2-MILESTONE-AUDIT.md closed
**Verified:** 2026-03-28
**Status:** gaps_found — SEC-04 (Cloud SQL backups) not satisfied due to GCP-imposed Free Trial instance restriction
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | AppServiceProvider validates DB_SOCKET (not DB_HOST) when unix_socket config is set | VERIFIED | `AppServiceProvider.php` lines 40-45: conditional branch reads `config('database.connections.mysql.unix_socket')`, sets `DB_SOCKET` when non-empty |
| 2 | AppServiceProvider validates DB_HOST when unix_socket config is empty (TCP mode) | VERIFIED | Same conditional falls through to `$required['DB_HOST']` in else branch; no unconditional DB_HOST entry found |
| 3 | Stale ci.yml at bite/.github/workflows/ci.yml is deleted | VERIFIED | `test ! -f .github/workflows/ci.yml` returns DELETED; `.github/` directory is fully absent; authoritative repo-root ci.yml preserved at `/backend/.github/workflows/ci.yml` |
| 4 | All 6 StartupValidation tests pass | VERIFIED | `php artisan test --filter=StartupValidation` — 6 passed (13 assertions); full suite 267 tests green |
| 5 | LOG_CHANNEL=stackdriver, FILESYSTEM_DISK=gcs, LIVEWIRE_TEMP_DISK=gcs, GCS_BUCKET, GOOGLE_CLOUD_PROJECT_ID, SENTRY_LARAVEL_DSN set on Cloud Run | VERIFIED | `gcloud run services describe` confirms all 6 vars with real values; health endpoint returns `{"status":"healthy","db":"ok","storage":"ok","gd_webp":"ok","queue":"ok"}` |
| 6 | Cloud SQL automated daily backups enabled with 7-day retention and PITR (SEC-04) | FAILED | `gcloud sql instances describe bite` returns `enabled: false, binaryLogEnabled: false` — GCP Free Trial instance restriction blocks backup config changes |

**Score:** 5/6 truths verified

---

## Required Artifacts

### Plan 01 Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `app/Providers/AppServiceProvider.php` | DB_SOCKET conditional validation | VERIFIED | Contains `config('database.connections.mysql.unix_socket')` conditional at lines 40-45; no unconditional DB_HOST assignment |
| `tests/Feature/StartupValidationTest.php` | 6 tests covering both socket/TCP branches | VERIFIED | 6 test methods present including `test_startup_validation_checks_db_socket_when_unix_socket_is_set` and `test_startup_validation_falls_back_to_host_check_in_tcp_mode` |
| `.github/workflows/ci.yml` (deleted) | File removed | VERIFIED | File absent; entire `.github/` directory gone |

### Plan 02 Artifacts (Infrastructure — no code files)

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| GCS bucket `bite-pos-storage` | Created in us-central1 | VERIFIED | `gcloud storage buckets describe gs://bite-pos-storage` confirms `name=bite-pos-storage, location=US-CENTRAL1` |
| Cloud Run env vars (6 new) | Set on `bite-pos-demo` service | VERIFIED | All 6 confirmed: `LOG_CHANNEL=stackdriver`, `FILESYSTEM_DISK=gcs`, `LIVEWIRE_TEMP_DISK=gcs`, `GCS_BUCKET=bite-pos-storage`, `GOOGLE_CLOUD_PROJECT_ID=ascent-web-260224-119`, `SENTRY_LARAVEL_DSN=https://affc14167252a080c7318932b444bb8f@o4511024693444608.ingest.de.sentry.io/...` |
| Cloud SQL backup config | `enabled: true`, `binaryLogEnabled: true` | FAILED | `enabled: false`, `binaryLogEnabled: false` — GCP Free Trial restriction |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `AppServiceProvider.php` | `config/database.php` unix_socket | `config('database.connections.mysql.unix_socket')` | WIRED | Pattern found at line 40; resolves to `env('DB_SOCKET', '')` from database.php |
| Cloud Run `LOG_CHANNEL` env var | `config/logging.php` stackdriver channel | `LOG_CHANNEL=stackdriver` | WIRED | Env var confirmed set on service; stackdriver channel wired in Phase 7 |
| Cloud Run `FILESYSTEM_DISK` env var | `config/filesystems.php` gcs disk | `FILESYSTEM_DISK=gcs` | WIRED | Env var confirmed; spatie GCS driver wired in Phase 6 |
| Cloud Run `SENTRY_LARAVEL_DSN` env var | Sentry ingestion | Real DSN | WIRED | Real DSN confirmed (not `https://dummy@sentry.io/0`) |
| Cloud SQL backup config | SEC-04 requirement | `gcloud sql instances patch` | NOT_WIRED | Command was attempted but rejected by GCP Free Trial restriction |

---

## Data-Flow Trace (Level 4)

Not applicable — this phase produced no components that render dynamic data. Changes are: startup validation logic (code-path, not rendering), test coverage, CI file deletion, and GCP infrastructure state.

---

## Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| StartupValidation — 6 tests pass | `php artisan test --filter=StartupValidation` | 6 passed (13 assertions) | PASS |
| Full test suite green | `php artisan test` | 267 passed (735 assertions) | PASS |
| Stale ci.yml absent | `test ! -f .github/workflows/ci.yml` | DELETED | PASS |
| AppServiceProvider unix_socket conditional | `grep -c 'unix_socket' AppServiceProvider.php` | 2 (comment + code) | PASS |
| No unconditional DB_HOST assignment | `grep "'DB_HOST' => config" AppServiceProvider.php` | NO_UNCONDITIONAL_DB_HOST | PASS |
| Cloud Run health endpoint | `curl https://bite-pos-demo-xe7go5rfiq-uc.a.run.app/health` | `{"status":"healthy","db":"ok","storage":"ok","gd_webp":"ok","queue":"ok","latency_ms":28}` | PASS |
| GCS bucket exists | `gcloud storage buckets describe gs://bite-pos-storage` | `bite-pos-storage US-CENTRAL1` | PASS |
| Cloud Run env vars set | `gcloud run services describe bite-pos-demo --format=yaml(env)` | All 6 vars present with real values | PASS |
| Cloud SQL backups enabled | `gcloud sql instances describe bite --format=yaml(backupConfiguration)` | `enabled: false` | FAIL |
| Code style | `./vendor/bin/pint --test` | `{"result":"pass"}` | PASS |
| Commits exist | `git log --oneline \| grep e0ff301\|abe4d8d` | Both commits found | PASS |

---

## Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| SEC-04 | 09-01-PLAN.md, 09-02-PLAN.md | Cloud SQL automated backups enabled with retention policy and point-in-time recovery | BLOCKED | `enabled: false, binaryLogEnabled: false` on Cloud SQL instance `bite`. GCP Free Trial restriction prevents backup configuration changes. Retry command documented in 09-02-SUMMARY.md. The requirement is mapped to Phase 9 in REQUIREMENTS.md but is not satisfied in production state. |

**Note on REQUIREMENTS.md status:** REQUIREMENTS.md line 29 shows `SEC-04` as `[x]` (complete). This is inaccurate — the actual GCP infrastructure state shows backups disabled. The checkbox was pre-marked by the SUMMARY documentation, not by verified infrastructure state. The true status is BLOCKED pending the GCP retry.

---

## Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| None found | — | — | — | — |

No TODO/FIXME, placeholder, or stub patterns found in modified files. AppServiceProvider uses real config values; test file simulates both code paths with real assertions.

---

## Human Verification Required

### 1. Sentry Exception Ingestion

**Test:** Trigger a 500 error on the live service (e.g., visit a URL that causes an unhandled exception in production) and check the Sentry dashboard at project `o4511024693444608`.
**Expected:** The exception appears in Sentry within 30 seconds with stack trace and environment context.
**Why human:** Cannot verify Sentry ingestion programmatically without generating a real exception and inspecting the dashboard.

### 2. GCS Product Image Upload

**Test:** Log into the admin panel on the live service, upload a product image, and confirm the image URL resolves to `storage.googleapis.com/bite-pos-storage/...`.
**Expected:** Image uploads succeed; GCS URL is served in the guest menu.
**Why human:** Cloud Run service account IAM permissions (`roles/storage.objectAdmin` on `bite-pos-storage`) cannot be verified with gcloud describe. An actual upload attempt is the definitive test.

### 3. Cloud SQL Backup Retry (Time-Gated)

**Test:** On 2026-03-29 or later, run: `gcloud sql instances patch bite --project=ascent-web-260224-119 --backup-start-time=02:00 --retained-backups-count=7 --enable-bin-log --retained-transaction-log-days=7`, then verify `gcloud sql instances describe bite --format="yaml(settings.backupConfiguration)"` shows `enabled: true, binaryLogEnabled: true`.
**Expected:** Command succeeds; backup config shows enabled with 7-day retention and PITR.
**Why human:** GCP-imposed time restriction cannot be bypassed programmatically; requires a human to run the retry after the restriction lifts.

---

## Gaps Summary

**One gap blocks full SEC-04 satisfaction.** The Cloud SQL instance `bite` was created on 2026-03-27. GCP imposes a "Free Trial Instance" restriction on all new Cloud SQL instances that blocks `Backup Configuration` and `PITR` operations for approximately 48-72 hours after creation. This is a time-based GCP restriction, not a code defect.

The retry command is documented in `09-02-SUMMARY.md` (Deferred Issues table). All other phase deliverables are complete and verified:

- AppServiceProvider DB_SOCKET conditional: fully wired and tested
- 6 StartupValidation tests: all passing
- Stale ci.yml: deleted (committed `abe4d8d`)
- GCS bucket `bite-pos-storage`: created and confirmed in us-central1
- Cloud Run env vars (all 6): set with real values, existing vars preserved
- Health endpoint: returning HTTP 200 with all subsystems healthy
- Real Sentry DSN: confirmed (not dummy placeholder)
- Full test suite: 267 tests green

The gap is purely an infrastructure timing constraint. When the GCP restriction lifts, a single 2-minute `gcloud sql instances patch` command closes SEC-04.

---

_Verified: 2026-03-28_
_Verifier: Claude (gsd-verifier)_

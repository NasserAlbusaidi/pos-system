# Phase 9: Production Activation & Gap Closure - Research

**Researched:** 2026-03-28
**Domain:** GCP Cloud SQL, Cloud Run env configuration, Sentry, Laravel AppServiceProvider
**Confidence:** HIGH

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| SEC-04 | Cloud SQL automated backups enabled with retention policy and point-in-time recovery | Instance is now on `db-perf-optimized-N-8` (paid tier). `backups.enabled=false` confirmed live. `gcloud sql instances patch` is now unblocked. Exact command documented in TODOS.md. |
</phase_requirements>

---

## Summary

Phase 9 is a gap-closure and activation phase: all the code and infrastructure exists from Phases 7 and 8, but several production services were left in "wired but not activated" states. The work is primarily GCP CLI commands, one env var update on Cloud Run, and one small Laravel code fix.

There are six discrete gaps to close, derived directly from `v1.2-MILESTONE-AUDIT.md`. Five are Cloud Run env var confirmations or GCP CLI operations. One is a ~5-line code change to `AppServiceProvider.php` to add `DB_SOCKET` validation. One is a file deletion (`bite/.github/workflows/ci.yml`).

**The most important finding from environment probing:** The Cloud SQL `bite` instance has already upgraded past the free trial tier (`db-perf-optimized-N-8`). The `gcloud sql instances patch` command for backups is now unblocked. Run it. This closes SEC-04.

**Primary recommendation:** Split into two plans — (1) GCP/infra operations (Cloud SQL backups, Cloud Run env vars, Sentry), and (2) Code changes (AppServiceProvider DB_SOCKET fix, stale ci.yml removal). Plan 2 is one GitHub Actions commit; Plan 1 is all gcloud CLI.

---

## Project Constraints (from CLAUDE.md)

- Laravel 12 + Livewire 3 (no traditional controllers for app features)
- Vanilla CSS — do NOT use Tailwind
- MySQL 8.0 production, SQLite in-memory for tests
- New migrations only — never modify existing migration files
- `use config() not env()` in production code (env() returns null after config:cache)
- No hardcoded secrets — all credentials via Cloud Run env vars
- Tests use SQLite in-memory — MySQL-specific features behave differently in tests
- `AppServiceProvider` uses `$this->app->environment('production')` guard so tests are unaffected
- Immutability: create new objects, never mutate (applies to code changes)
- Functions < 50 lines, files < 800 lines

---

## Current State (Live Environment Probed)

All findings are from direct GCP queries run on 2026-03-28.

### Cloud SQL Instance `bite` (project: ascent-web-260224-119)

| Property | Value |
|----------|-------|
| Tier | `db-perf-optimized-N-8` (PAID — free trial constraint is GONE) |
| State | RUNNABLE |
| DB Version | MYSQL_8_4 |
| Backups enabled | `false` |
| Binary log | `false` |
| Backup start time | 07:00 (default, unused) |

**Conclusion:** The free trial limitation documented in TODOS.md and `08-VERIFICATION.md` no longer applies. Backups can be enabled now with the command already in TODOS.md.

### Cloud Run Service `bite-pos-demo` (us-central1)

Current env vars set on Cloud Run (from `gcloud run services describe`):

| Env Var | Current Value | Required Value |
|---------|--------------|----------------|
| `SENTRY_LARAVEL_DSN` | `https://dummy@sentry.io/0` | Real DSN from Sentry project |
| `DB_CONNECTION` | `mysql` | OK |
| `DB_DATABASE` | `bite_pos` | OK |
| `DB_USERNAME` | `bite` | OK |
| `DB_SOCKET` | `/cloudsql/ascent-web-260224-119:us-central1:bite` | OK |
| `DB_PASSWORD` | set | OK |
| `APP_KEY` | set | OK |
| `APP_DEBUG` | `false` | OK |
| `LOG_CHANNEL` | **NOT SET** | `stackdriver` |
| `FILESYSTEM_DISK` | **NOT SET** | `gcs` |
| `LIVEWIRE_TEMP_DISK` | **NOT SET** | `gcs` |
| `GCS_BUCKET` | **NOT SET** | bucket name |
| `GOOGLE_CLOUD_PROJECT_ID` | **NOT SET** | `ascent-web-260224-119` |
| `DB_HOST` | **NOT SET** | not needed (socket-only) |

**Key observation:** `DB_HOST` is intentionally absent — the app connects via `DB_SOCKET`. This is the HARD-02 gap: `AppServiceProvider` validates `DB_HOST` but not `DB_SOCKET`. Since Cloud SQL Auth Proxy uses a socket, if the socket path were wrong the startup check would not catch it.

### GCS Bucket

Available buckets in project (none appear to be the app storage bucket):
- `ascent-web-260224-119.firebasestorage.app`
- `ascent-web-260224-119_cloudbuild`
- `firebaseapphosting-sources-528372920943-us-central1`
- `run-sources-ascent-web-260224-119-us-central1`

**Finding:** No dedicated app GCS bucket is visible. The Phase 6 work set up GCS storage — a bucket name must exist. This needs confirmation from Nasser before FILESYSTEM_DISK=gcs can be set. If the bucket was created during Phase 6 or 8, it may not be visible here. This is the one item in Phase 9 that requires human confirmation before the plan task can execute.

### Stale ci.yml

- **Authoritative file:** `/Users/nasseralbusaidi/Desktop/Personal/POS/backend/.github/workflows/ci.yml` (156 lines, full CI/CD pipeline)
- **Stale copy:** `/Users/nasseralbusaidi/Desktop/Personal/POS/backend/bite/.github/workflows/ci.yml` (55 lines, old Dusk E2E workflow, PHP 8.2, never executed by GitHub Actions)
- **Action:** Delete the stale copy. The repo root `.github/workflows/ci.yml` is what GitHub Actions reads.

---

## Standard Stack

### Core (all already installed)

| Tool | Version | Purpose | Notes |
|------|---------|---------|-------|
| `gcloud` CLI | 558.0.0 | GCP infrastructure commands | Available, authenticated as `nasserbusaidi@gmail.com`, project `ascent-web-260224-119` |
| PHP | 8.4.17 | Runtime | Available |
| Composer | 2.9.2 | Package manager | Available |
| `sentry/sentry-laravel` | installed | Error tracking | Package present, DSN is dummy |

### No New Packages Required

Phase 9 installs nothing new. All code already exists from Phase 7. This phase is purely:
1. GCP CLI commands (gcloud)
2. Cloud Run env var updates (gcloud run services update)
3. One small PHP code change (AppServiceProvider)
4. One file deletion (stale ci.yml)

---

## Architecture Patterns

### Pattern 1: Cloud Run env var update

**What:** `gcloud run services update` with `--update-env-vars` flag. Sets/overwrites specific env vars without touching others.

**When to use:** When adding new env vars to an existing deployment.

```bash
# Source: gcloud CLI reference (HIGH confidence — probed locally)
gcloud run services update bite-pos-demo \
  --region=us-central1 \
  --project=ascent-web-260224-119 \
  --update-env-vars="LOG_CHANNEL=stackdriver,FILESYSTEM_DISK=gcs,LIVEWIRE_TEMP_DISK=gcs,GCS_BUCKET=<bucket>,GOOGLE_CLOUD_PROJECT_ID=ascent-web-260224-119,SENTRY_LARAVEL_DSN=<real-dsn>"
```

**Important:** `--update-env-vars` is additive — existing vars not mentioned are preserved. Use `--set-env-vars` only to replace ALL env vars (destructive). Use `--update-env-vars` here.

**After update:** Cloud Run deploys a new revision automatically. The new revision inherits all previous env vars plus the updated ones.

### Pattern 2: Cloud SQL backup enablement

**What:** `gcloud sql instances patch` enables automated backups and binary logging.

**From TODOS.md (verified against current GCP state):**

```bash
gcloud sql instances patch bite \
  --project=ascent-web-260224-119 \
  --backup-start-time=02:00 \
  --retained-backups-count=7 \
  --enable-bin-log \
  --retained-transaction-log-days=7
```

**Verification command:**
```bash
gcloud sql instances describe bite \
  --project=ascent-web-260224-119 \
  --format="yaml(settings.backupConfiguration)"
```

**Expected result after patch:**
```yaml
settings:
  backupConfiguration:
    enabled: true
    binaryLogEnabled: true
    startTime: 02:00
    backupRetentionSettings:
      retainedBackups: 7
    transactionLogRetentionDays: 7
```

**Note:** `--enable-bin-log` enables binary logging which is required for point-in-time recovery (PITR). This adds minor write overhead (~5% I/O) but is standard for production MySQL.

### Pattern 3: AppServiceProvider DB_SOCKET validation

**What:** The existing startup validation checks `DB_HOST` but the production Cloud Run deployment uses `DB_SOCKET` (Cloud SQL Auth Proxy socket path). When `DB_HOST` is not explicitly set, `DB_SOCKET` should be validated instead.

**Current code (AppServiceProvider.php line 33):**
```php
'DB_HOST' => config('database.connections.mysql.host'),
```

**Problem:** `config('database.connections.mysql.host')` returns `'127.0.0.1'` (the default from `database.php` line 49: `'host' => env('DB_HOST', '127.0.0.1')`). This means the check NEVER fails even when `DB_HOST` is not set — the default value satisfies the non-empty check.

**Fix approach:** Check `DB_SOCKET` when `DB_HOST` is not explicitly set:

```php
// config('database.connections.mysql.host') always has a default ('127.0.0.1')
// so checking it never detects a missing DB_HOST env var.
// The correct check for socket-only Cloud SQL deployments:
if (empty(env('DB_HOST'))) {
    $required['DB_SOCKET'] = config('database.connections.mysql.unix_socket');
} else {
    $required['DB_HOST'] = config('database.connections.mysql.host');
}
```

**Constraint:** The CLAUDE.md rule says "use config() not env() in production code — env() returns null after config:cache." The check here uses `env('DB_HOST')` only as a branching condition (to determine which path to validate), while the actual value passed to `$required` uses `config()`. This is acceptable — the branch condition does not need the value, only its presence/absence. Alternatively, the simpler approach is to use the raw env value:

```php
// Simpler: validate DB_SOCKET when it is set (socket-only mode)
$dbSocket = config('database.connections.mysql.unix_socket');
if (! empty($dbSocket)) {
    // Socket-only mode (Cloud SQL Auth Proxy)
    $required['DB_SOCKET'] = $dbSocket;
} else {
    // TCP mode — host required
    $required['DB_HOST'] = config('database.connections.mysql.host');
}
```

This approach uses `config()` throughout and avoids the `env()` issue entirely. The `unix_socket` config key maps to `env('DB_SOCKET', '')` in `database.php`, so it will be empty on dev (no socket set) and populated on Cloud Run (socket set via env var).

**Test coverage:** `StartupValidationTest.php` exists with 4 test methods. A new test case for `DB_SOCKET` validation is required. The test replicates the AppServiceProvider logic directly (same pattern as existing tests).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Cloud SQL backup scheduling | Custom cron job | `gcloud sql instances patch --enable-bin-log` | GCP-managed backups are atomic, consistent, and integrated with PITR — custom cron mysqldump is not |
| Env var management | Shell scripts | `gcloud run services update --update-env-vars` | Cloud Run manages revision rollout, traffic splitting, and env var inheritance |
| Sentry project creation | — | Sentry web UI (manual step) | Requires human action — cannot be automated without Sentry API token |

---

## Common Pitfalls

### Pitfall 1: `--set-env-vars` vs `--update-env-vars`

**What goes wrong:** Using `--set-env-vars` replaces ALL env vars on the Cloud Run service. Existing vars like `DB_PASSWORD`, `APP_KEY`, `DB_SOCKET` are wiped.
**Why it happens:** Both flags look similar; `--set-env-vars` is the "obvious" one.
**How to avoid:** Always use `--update-env-vars` for additive changes. Only use `--set-env-vars` if you want to replace the entire env var set.
**Warning signs:** Cloud Run service fails health check after update (DB connection lost).

### Pitfall 2: Cloud SQL backup patch triggers a brief maintenance window

**What goes wrong:** `gcloud sql instances patch` with backup changes may cause a brief (seconds) instance restart.
**Why it happens:** MySQL binary log requires InnoDB configuration change.
**How to avoid:** Run during low-traffic window (02:00-04:00 UTC). For Oman timezone (UTC+4), 02:00 UTC = 06:00 local — acceptable.
**Warning signs:** Brief 5xx errors in Cloud Run during patch application.

### Pitfall 3: `config()` vs `env()` in AppServiceProvider

**What goes wrong:** Using `env('DB_HOST')` directly in AppServiceProvider after `php artisan config:cache` returns null — the validation check silently passes when it should fail.
**Why it happens:** `config:cache` serializes config to a PHP file; `env()` calls bypass this cache and return null.
**How to avoid:** Use `config('database.connections.mysql.unix_socket')` to read the DB_SOCKET value — this reads from the cached config. The existing codebase already follows this pattern for all other validations.
**Warning signs:** Startup validation not catching missing env vars in staging tests.

### Pitfall 4: GCS bucket not found / wrong bucket name

**What goes wrong:** Setting `FILESYSTEM_DISK=gcs` and `GCS_BUCKET=<wrong-name>` causes all file operations to fail silently (GCS driver throws on missing bucket).
**Why it happens:** The Phase 6 bucket name is not documented in any committed file (it was set up manually during Phase 6/8).
**How to avoid:** Verify the exact bucket name with `gcloud storage buckets list --project=ascent-web-260224-119` before setting the env var. The app bucket was provisioned during Phase 6 — if it does not appear in the list, it may be in a different project or named differently.
**Warning signs:** `/health` endpoint returns `"storage":"error"` after env var update.

### Pitfall 5: HARD-02 test coverage gap

**What goes wrong:** The existing `StartupValidationTest` does not test the DB_SOCKET branch. After the AppServiceProvider fix, a Cloud SQL socket misconfiguration would pass validation in tests but fail in production.
**How to avoid:** Add a test case that sets `DB_SOCKET` (unix_socket config) and verifies validation includes it. Mirror the existing test structure.

---

## Code Examples

### AppServiceProvider DB_SOCKET Fix

```php
// Source: AppServiceProvider.php (existing pattern + fix)
// Using config() throughout — env() is never called after config:cache

if ($this->app->environment('production')) {
    $required = [
        'APP_KEY' => config('app.key'),
        'DB_DATABASE' => config('database.connections.mysql.database'),
    ];

    // Cloud SQL Auth Proxy uses unix socket — validate socket when host is default/empty
    $dbSocket = config('database.connections.mysql.unix_socket');
    if (! empty($dbSocket)) {
        $required['DB_SOCKET'] = $dbSocket;
    } else {
        $required['DB_HOST'] = config('database.connections.mysql.host');
    }

    // GCS vars only required when filesystem is gcs
    if (config('filesystems.default') === 'gcs') {
        $required['GCS_BUCKET'] = config('filesystems.disks.gcs.bucket');
        $required['GOOGLE_CLOUD_PROJECT_ID'] = config('filesystems.disks.gcs.project_id');
    }

    // Sentry DSN required in production
    $required['SENTRY_LARAVEL_DSN'] = config('sentry.dsn');

    $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

    if (! empty($missing)) {
        throw new \RuntimeException(
            'Missing required environment variables: '.implode(', ', $missing)
        );
    }
}
```

**Note:** The original code had `'DB_HOST' => config('database.connections.mysql.host')` which always returns `'127.0.0.1'` (the hardcoded default in database.php). This check can never fail. The fix removes it from the always-checked list and moves it to the conditional branch.

### Test Case for DB_SOCKET Branch

```php
// Source: StartupValidationTest.php pattern (existing structure)
public function test_startup_validation_checks_db_socket_when_unix_socket_is_set(): void
{
    Config::set('database.connections.mysql.unix_socket', '/cloudsql/project:region:instance');
    Config::set('database.connections.mysql.host', '127.0.0.1'); // default, not explicitly set

    $dbSocket = Config::get('database.connections.mysql.unix_socket');
    $required = [];

    if (! empty($dbSocket)) {
        $required['DB_SOCKET'] = $dbSocket;
    } else {
        $required['DB_HOST'] = Config::get('database.connections.mysql.host');
    }

    $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

    $this->assertEmpty($missing, 'DB_SOCKET present — no missing vars expected');
    $this->assertArrayNotHasKey('DB_HOST', $required, 'DB_HOST should not be checked in socket mode');
}

public function test_startup_validation_fails_when_db_socket_is_missing_in_socket_mode(): void
{
    Config::set('database.connections.mysql.unix_socket', ''); // socket not set
    Config::set('database.connections.mysql.host', '127.0.0.1'); // default

    $dbSocket = Config::get('database.connections.mysql.unix_socket');
    $required = [];

    if (! empty($dbSocket)) {
        $required['DB_SOCKET'] = $dbSocket;
    } else {
        // Falls back to host check
        $required['DB_HOST'] = Config::get('database.connections.mysql.host');
    }

    $missing = array_keys(array_filter($required, fn ($value) => empty($value)));

    // 127.0.0.1 is not empty — host check passes (TCP mode, valid)
    $this->assertEmpty($missing);
}
```

---

## Runtime State Inventory

This phase modifies live Cloud Run env vars and Cloud SQL instance settings — both are runtime state, not git state.

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | Cloud SQL `bite` instance — backup settings off | `gcloud sql instances patch` to enable backups and binary log |
| Live service config | Cloud Run `bite-pos-demo` — `LOG_CHANNEL`, `FILESYSTEM_DISK`, `LIVEWIRE_TEMP_DISK`, `GCS_BUCKET`, `GOOGLE_CLOUD_PROJECT_ID`, `SENTRY_LARAVEL_DSN` not set or dummy | `gcloud run services update --update-env-vars` |
| OS-registered state | None found | None |
| Secrets/env vars | `SENTRY_LARAVEL_DSN` is dummy on Cloud Run — must be replaced with real DSN (requires Sentry project creation first) | Create Sentry project (manual), then update Cloud Run env var |
| Build artifacts | Stale `bite/.github/workflows/ci.yml` (55 lines, old Dusk workflow) — never executed, misleading | Delete the file — `git rm bite/.github/workflows/ci.yml` |

**GCS bucket name unknown:** The application GCS bucket was provisioned during Phase 6/8 but is not recorded in any committed file. Four buckets are visible in the project, none with an obvious app-storage name. Nasser must identify the correct bucket name before `FILESYSTEM_DISK=gcs` can be activated. This is the one human-dependency in this phase.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| `gcloud` CLI | Cloud SQL patch, Cloud Run update | YES | 558.0.0 | None needed |
| GCP auth | All gcloud commands | YES | `nasserbusaidi@gmail.com` active | None needed |
| GCP project `ascent-web-260224-119` | All infra commands | YES | Active | None |
| Cloud Run `bite-pos-demo` (us-central1) | Env var updates | YES | Revision 00026 | None |
| Cloud SQL `bite` instance | Backup enablement | YES | RUNNABLE, paid tier | None |
| Sentry account | Real DSN | UNKNOWN | — | Phase 9 must prompt human to create project |
| GCS app bucket | FILESYSTEM_DISK=gcs | UNKNOWN | — | Must confirm bucket name before activating |

**Missing dependencies with no fallback:**
- Sentry DSN — requires Nasser to create a Sentry project (free tier available at sentry.io). Cannot be automated. Must be done before the Cloud Run env var update for Sentry.
- GCS bucket name — must be confirmed before setting `FILESYSTEM_DISK=gcs`.

**Missing dependencies with fallback:**
- If Sentry DSN is not available at plan execution time, the other env vars (LOG_CHANNEL, FILESYSTEM_DISK, LIVEWIRE_TEMP_DISK) can be set first, and Sentry updated in a separate step.

---

## Validation Architecture

nyquist_validation is `true` in `.planning/config.json` — this section is required.

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit (via Laravel test runner) |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --filter=StartupValidation` |
| Full suite command | `composer test` |

### Phase Requirements to Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SEC-04 | Cloud SQL backups enabled with PITR | manual-only | `gcloud sql instances describe bite --format="yaml(settings.backupConfiguration)"` | N/A (infra) |
| HARD-02 gap | AppServiceProvider validates DB_SOCKET when socket mode | unit | `php artisan test --filter=StartupValidation` | YES (but new test cases needed) |
| HARD-04 activation | LOG_CHANNEL=stackdriver set on Cloud Run | manual-only | `gcloud run services describe bite-pos-demo --format="yaml(spec.template.spec.containers[0].env)"` | N/A (infra) |

### Sampling Rate

- **Per task commit:** `php artisan test --filter=StartupValidation`
- **Per wave merge:** `composer test` (full 265-test suite)
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Feature/StartupValidationTest.php` — add 2 new test cases for DB_SOCKET branch (file exists, tests need adding)

*(All other phase goals are infrastructure changes with no automated test equivalent — verified via gcloud CLI commands)*

---

## Open Questions

1. **GCS bucket name**
   - What we know: Bucket was created during Phase 6/8 setup. Four GCP-managed buckets visible but none appears to be the app storage bucket.
   - What's unclear: Was the app bucket created with a custom name? Was it created in the same project? Was it already used with a real key?
   - Recommendation: Planner should include a verification step — `gcloud storage buckets list --project=ascent-web-260224-119` — and have Nasser identify/confirm the correct bucket name before setting `FILESYSTEM_DISK=gcs`.

2. **Sentry project existence**
   - What we know: `SENTRY_LARAVEL_DSN` is a dummy value on Cloud Run. The `sentry/sentry-laravel` package is installed and wired.
   - What's unclear: Has a Sentry project been created? Does a real DSN exist anywhere?
   - Recommendation: Plan should include a prompt for Nasser to create a Sentry project (free tier, takes 2 minutes at sentry.io), then copy the DSN into the Cloud Run update command.

3. **Cloud SQL PITR after binary log enable**
   - What we know: The instance is `db-perf-optimized-N-8`. `binaryLogEnabled: false` currently.
   - What's unclear: Does enabling binary log trigger a brief restart? On paid Cloud SQL tiers, binary log enablement via patch is usually applied at next maintenance window or immediately.
   - Recommendation: Run the patch and verify `gcloud sql operations list --instance=bite` to confirm the operation completed successfully.

---

## Sources

### Primary (HIGH confidence)
- Live GCP probing via `gcloud sql instances describe bite` — Cloud SQL tier and backup config confirmed
- Live GCP probing via `gcloud run services describe bite-pos-demo` — Cloud Run env vars confirmed
- Direct file reads: `AppServiceProvider.php`, `config/database.php`, `config/logging.php`, `config/filesystems.php`, `StartupValidationTest.php`
- `v1.2-MILESTONE-AUDIT.md` — definitive list of gaps from Phase 8

### Secondary (MEDIUM confidence)
- `TODOS.md` — exact gcloud command for SEC-04 (confirmed correct against current instance state)
- `08-VERIFICATION.md` — Phase 8 gap documentation (all findings re-verified against live GCP)

### Tertiary (LOW confidence)
- Cloud SQL binary log restart behavior — based on GCP documentation patterns, not tested live

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new packages; all tools probed live
- Architecture: HIGH — all six gaps identified from live GCP probe + audit report
- Pitfalls: HIGH — DB_SOCKET/default-host bug confirmed by reading database.php defaults; env var flag confusion confirmed by gcloud docs
- Infra state: HIGH — Cloud SQL and Cloud Run probed directly

**Research date:** 2026-03-28
**Valid until:** 2026-04-28 (stable domain — GCP CLI and Laravel AppServiceProvider are not fast-moving)

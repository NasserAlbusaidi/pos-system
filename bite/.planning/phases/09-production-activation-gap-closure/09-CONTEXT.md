# Phase 9: Production Activation & Gap Closure - Context

**Gathered:** 2026-03-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Activate all production services left in "wired but not activated" states from Phases 7-8, and close gaps identified in v1.2-MILESTONE-AUDIT.md. Specifically: enable Cloud SQL automated backups with PITR (SEC-04), set missing Cloud Run env vars (LOG_CHANNEL, FILESYSTEM_DISK, LIVEWIRE_TEMP_DISK, GCS_BUCKET, GOOGLE_CLOUD_PROJECT_ID, SENTRY_LARAVEL_DSN), fix AppServiceProvider DB_SOCKET validation (HARD-02 gap), and remove stale ci.yml. No new application features.

</domain>

<decisions>
## Implementation Decisions

### GCS Bucket
- **D-01:** Plan must verify if a GCS bucket already exists for the app via `gcloud storage buckets list`. If none found, create `bite-pos-storage` in `us-central1` with standard storage class.
- **D-02:** Bucket name for `GCS_BUCKET` env var is `bite-pos-storage` (or whichever existing bucket is identified in verification step).

### Sentry Project
- **D-03:** Nasser does not have a Sentry account yet. Plan includes a **blocking manual step** for Nasser to create a free Sentry account at sentry.io, create a Laravel project, and provide the DSN.
- **D-04:** All env vars (including SENTRY_LARAVEL_DSN) are set in a single update — Sentry DSN must be obtained before the Cloud Run update command runs.

### Env Var Rollout
- **D-05:** **All env vars updated in a single `gcloud run services update --update-env-vars` command.** One new revision, one health check. Auto-rollback from Phase 8 pipeline protects against failures.
- **D-06:** Env vars to set: `LOG_CHANNEL=stackdriver`, `FILESYSTEM_DISK=gcs`, `LIVEWIRE_TEMP_DISK=gcs`, `GCS_BUCKET=<confirmed-bucket>`, `GOOGLE_CLOUD_PROJECT_ID=ascent-web-260224-119`, `SENTRY_LARAVEL_DSN=<real-dsn>`.

### Backup Timing
- **D-07:** Cloud SQL backup patch can run anytime — service is not yet live with real customers (Sourdough hasn't launched). No scheduling constraint.
- **D-08:** Backup config: daily automated backups at 02:00, 7-day retention, binary log enabled, 7-day transaction log retention (inherited from Phase 8 decisions D-09, D-10).

### Claude's Discretion
- Exact `gsutil mb` flags if bucket creation is needed (storage class, location, uniform access)
- GCS bucket IAM permissions setup (service account access)
- Post-update verification sequence (which health checks to run, in what order)
- DB_SOCKET validation implementation details (already well-documented in research)
- Test structure for new StartupValidationTest cases

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Gap Closure Source
- `.planning/v1.2-MILESTONE-AUDIT.md` — Definitive list of gaps this phase closes

### Infrastructure
- `app/Providers/AppServiceProvider.php` — Startup validation code to fix (DB_SOCKET gap)
- `config/database.php` — DB_SOCKET/unix_socket mapping (line 54, 74)
- `config/logging.php` — Stackdriver channel configuration
- `config/filesystems.php` — GCS disk configuration
- `config/sentry.php` — Sentry DSN and sample rate config

### Health Check (Post-Update Verification)
- `app/Http/Controllers/HealthCheckController.php` — GET /health endpoint for post-update verification

### Tests
- `tests/Feature/StartupValidationTest.php` — Existing startup validation tests (add DB_SOCKET cases)

### CI/CD
- `.github/workflows/ci.yml` — Authoritative CI/CD workflow (repo root)
- `bite/.github/workflows/ci.yml` — Stale copy to DELETE (55 lines, old Dusk workflow)

### Prior Phase Context
- `.planning/phases/08-ci-cd-data-safety/08-CONTEXT.md` — Backup retention decisions (D-09, D-10), env var approach
- `.planning/phases/07-hardening-security/07-CONTEXT.md` — Logging, Sentry, health check decisions
- `.planning/phases/09-production-activation-gap-closure/09-RESEARCH.md` — Live GCP state probing results, exact gcloud commands

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AppServiceProvider.php` startup validation block — Existing pattern checks required env vars in production. Fix adds DB_SOCKET branch.
- `HealthCheckController.php` — Returns JSON status of DB, storage, GD, queue. Use for post-update verification.
- `StartupValidationTest.php` — 4 existing test methods. Add 2 new cases for DB_SOCKET branch.

### Established Patterns
- `config()` not `env()` in production code — AppServiceProvider fix must use `config('database.connections.mysql.unix_socket')` not `env('DB_SOCKET')`
- `$this->app->environment('production')` guard — Startup validation only runs in production, tests unaffected
- `--update-env-vars` (additive) not `--set-env-vars` (destructive) — From Phase 8 learnings

### Integration Points
- Cloud Run `bite-pos-demo` service — Target for env var update
- Cloud SQL `bite` instance — Target for backup enablement
- `config/filesystems.php` GCS disk — Already configured by Phase 6, just needs env vars populated

</code_context>

<specifics>
## Specific Ideas

- Cloud SQL instance `bite` is on `db-perf-optimized-N-8` (paid tier) — backup enablement is unblocked
- Current Cloud Run revision is 00026 — env var update will create revision 00027
- Research confirmed `DB_HOST` is NOT set on Cloud Run (socket-only mode via `DB_SOCKET`)
- `config('database.connections.mysql.host')` returns `'127.0.0.1'` (hardcoded default) — current validation can never fail for DB_HOST
- Four GCP buckets visible but none looks like app storage — bucket creation likely needed

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 09-production-activation-gap-closure*
*Context gathered: 2026-03-28*

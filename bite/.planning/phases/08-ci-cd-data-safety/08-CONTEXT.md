# Phase 8: CI/CD & Data Safety - Context

**Gathered:** 2026-03-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Automated test-build-deploy pipeline via GitHub Actions that tests, builds a Docker image, pushes to Artifact Registry, and deploys to Cloud Run on every push to main. Cloud SQL automated backups with point-in-time recovery for data safety. No new application features — pure infrastructure and DevOps.

</domain>

<decisions>
## Implementation Decisions

### Deployment Trigger & Gating
- **D-01:** **Auto-deploy on push to main.** Tests pass -> build image -> push to Artifact Registry -> deploy to Cloud Run. No manual approval gate.
- **D-02:** **PRs run tests + lint + Docker build (no push/deploy).** Catches Dockerfile and asset build failures before merge. Only pushes to main trigger the full deploy pipeline.
- **D-03:** **Remove path filter (`bite/**`).** Any push to main triggers the pipeline. The `bite/` subdirectory structure was a leftover — the repo root IS the app.
- **D-04:** **Remove `working-directory: bite`.** Same reason as D-03 — no subdirectory nesting.

### Post-Deploy Verification
- **D-05:** **Pipeline hits GET /health after deploy.** Wait 30 seconds after `gcloud run deploy`, then check the health endpoint. Phase 7's health check verifies DB, storage, GD/WebP, and queue.
- **D-06:** **Auto-rollback on health check failure.** If /health returns non-200, the pipeline runs `gcloud run services update-traffic` to shift 100% back to the previous revision. Production stays safe automatically.
- **D-07:** **GitHub Actions email for failure notifications.** No additional notification channels (Slack, etc.) — solo founder, built-in email is sufficient.
- **D-08:** **Migrations run at container startup** (existing `docker/start.sh` behavior). Not in the pipeline — no DB access needed from GitHub Actions.

### Backup & Recovery Policy (SEC-04)
- **D-09:** **7-day backup retention.** Cloud SQL automated daily backups retained for 7 days.
- **D-10:** **Point-in-time recovery (PITR) enabled.** Binary logging allows restore to any specific second within the retention window. Critical for a POS system — can restore to the moment before bad data was written.
- **D-11:** **Backup config documented in plan only.** No separate runbook file — the plan captures the gcloud commands for verification and restore procedures.

### Image Tagging & Rollback
- **D-12:** **Tag images with commit SHA + `latest`.** Each image tagged as `bite-pos:<commit-sha>` AND `bite-pos:latest`. SHA provides exact traceability, `latest` gives Cloud Run a simple default.
- **D-13:** **Keep all images indefinitely.** No automatic cleanup policy. Storage is cheap. Every deployed image remains available for rollback.
- **D-14:** **Use existing `cloud-run-source-deploy` Artifact Registry repo.** Already exists in `us-central1` (same region as Cloud Run). No need to create a new repo.

### GCP Configuration
- **D-15:** **GCP credentials stored as GitHub secrets.** `GCP_PROJECT_ID` (`ascent-web-260224-119`), `CLOUD_RUN_SERVICE` (`bite-pos-demo`), `GCP_REGION` (`us-central1`) configured as GitHub Actions secrets.

### Claude's Discretion
- GitHub Actions authentication method for GCP (Workload Identity Federation vs service account key)
- Docker build caching strategy in CI
- Exact health check retry logic (how many retries, backoff)
- Cloud SQL backup window timing (time of day for daily backup)
- Artifact Registry image path format

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### CI/CD Pipeline
- `.github/workflows/ci.yml` — Current CI workflow (tests + lint only, no deploy). Must be extended with build + deploy steps. Remove `working-directory: bite` and path filter.
- `Dockerfile` — Production-ready multi-stage build (Node frontend + PHP-FPM app). Pipeline builds this and pushes to Artifact Registry.
- `docker/start.sh` — Container startup script (migrations, config caching, supervisord). Runs at container boot, not in pipeline.

### Container Configuration
- `docker/nginx.conf` — Nginx config for the production container
- `docker/php-fpm.conf` — PHP-FPM pool config
- `docker/supervisord.conf` — Process manager config (Nginx + PHP-FPM)

### Health Check (Post-Deploy Verification)
- `app/Http/Controllers/HealthCheckController.php` — GET /health endpoint built in Phase 7. Pipeline uses this for post-deploy verification.

### Requirements
- `.planning/REQUIREMENTS.md` — DEPLOY-04 (CI/CD pipeline), SEC-04 (Cloud SQL backups) mapped to this phase

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `ci.yml` — Working CI workflow with PHP setup, Composer caching, test execution, and Pint lint check. Extend with Docker build + deploy steps.
- `Dockerfile` — Production-ready multi-stage build. No changes needed — pipeline just builds it.
- `docker/start.sh` — Handles migrations and cache warming at startup. No pipeline changes needed.
- Health check endpoint — Built in Phase 7, returns JSON status of all subsystems.

### Established Patterns
- GitHub Actions with `shivammathur/setup-php@v2` — Already set up for PHP 8.2 with SQLite extension for tests.
- `actions/cache@v4` for Composer dependencies — Already configured with proper cache key.
- Cloud Run deployment — Service `bite-pos-demo` already running in `us-central1`.

### Integration Points
- `.github/workflows/ci.yml` — Extend with Docker build, Artifact Registry push, Cloud Run deploy, and health check verification steps.
- GitHub repository settings — Add secrets: `GCP_PROJECT_ID`, `GCP_SA_KEY` (or Workload Identity), `CLOUD_RUN_SERVICE`, `GCP_REGION`.
- Cloud SQL instance settings — Enable automated backups and PITR via `gcloud sql instances patch`.

</code_context>

<specifics>
## Specific Ideas

- The existing `bite-pos-demo` Cloud Run service is already running — this pipeline automates what's currently a manual deploy process
- Artifact Registry repo `cloud-run-source-deploy` already exists in `us-central1` — no repo creation needed
- GCP project `ascent-web-260224-119` hosts both Cloud Run and Artifact Registry
- Health check endpoint from Phase 7 is the natural verification target post-deploy

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 08-ci-cd-data-safety*
*Context gathered: 2026-03-27*

# Roadmap: Bite-POS

## Milestones

- ✅ **v1.0 Sourdough Demo** — Phases 1-2 (shipped 2026-03-21)
- ✅ **v1.1 Customization & Polish** — Phases 3-5 (shipped 2026-03-21)
- 🚧 **v1.2 Production Readiness** — Phases 6-9 (in progress)

## Phases

<details>
<summary>✅ v1.0 Sourdough Demo (Phases 1-2) — SHIPPED 2026-03-21</summary>

- [x] Phase 1: Polish (3/3 plans) — completed 2026-03-20
- [x] Phase 2: Demo (2/2 plans) — completed 2026-03-21

See: `.planning/milestones/v1.0-ROADMAP.md` for full details

</details>

<details>
<summary>✅ v1.1 Customization & Polish (Phases 3-5) — SHIPPED 2026-03-21</summary>

- [x] Phase 3: Item Availability (2/2 plans) — completed 2026-03-21
- [x] Phase 4: Image Optimization (2/2 plans) — completed 2026-03-21
- [x] Phase 5: Menu Themes (2/2 plans) — completed 2026-03-21

See: `.planning/milestones/v1.1-ROADMAP.md` for full details

</details>

### 🚧 v1.2 Production Readiness

**Milestone Goal:** Deploy Bite-POS to Google Cloud Run with production-grade infrastructure, hardening, and security — ready for Sourdough Oman as first live customer.

- [x] **Phase 6: Containerization & Cloud Services** - App runs in Docker with Cloud SQL and Cloud Storage, secrets managed via environment (completed 2026-03-27)
- [x] **Phase 7: Hardening & Security** - Production-grade health checks, rate limiting, observability, tenant isolation audit, and input validation (completed 2026-03-27)
- [x] **Phase 8: CI/CD & Data Safety** - Automated test-build-deploy pipeline and database backup strategy (completed 2026-03-27)
- [x] **Phase 9: Production Activation & Gap Closure** - Activate production services (backups, logging, Sentry), close audit gaps, minor code fixes (completed 2026-03-27)

## Phase Details

### Phase 6: Containerization & Cloud Services
**Goal**: App runs as a containerized service connected to managed cloud database and storage, with no hardcoded secrets
**Depends on**: Phase 5 (v1.1 complete)
**Requirements**: DEPLOY-01, DEPLOY-02, DEPLOY-03, SEC-02
**Plans:** 1/2 plans executed
Plans:
- [x] 06-01-PLAN.md — Production container with Nginx + PHP-FPM, Cloud SQL MySQL config, secrets enforcement
- [x] 06-02-PLAN.md — GCS storage migration for product images, ImageService refactor, Livewire temp uploads
**Success Criteria** (what must be TRUE):
  1. App boots in a Docker container with PHP-FPM + Nginx serving requests and returning correct responses
  2. App reads and writes data to Cloud SQL MySQL 8.0 via Cloud SQL Auth Proxy without connection errors
  3. Uploading a product image stores it in Google Cloud Storage and the guest menu displays it from a GCS URL
  4. Container runs with zero secrets in the image — all credentials come from Cloud Run environment variables or Secret Manager
  5. Running `docker build` produces a working image with Composer deps installed and Vite assets compiled

### Phase 7: Hardening & Security
**Goal**: App is production-hardened with health monitoring, rate limiting, structured logging, and verified security boundaries
**Depends on**: Phase 6
**Requirements**: HARD-01, HARD-02, HARD-03, HARD-04, SEC-01, SEC-03
**Plans:** 3/3 plans complete
Plans:
- [x] 07-01-PLAN.md — Health check endpoint, startup env validation, rate limiting (HARD-01, HARD-02, HARD-03)
- [x] 07-02-PLAN.md — Structured JSON logging, PII masking, slow request detection (HARD-04)
- [x] 07-03-PLAN.md — Tenant isolation audit with regression tests, input validation sweep (SEC-01, SEC-03)
**Success Criteria** (what must be TRUE):
  1. GET /health returns status of DB connectivity, storage access, GD extension, and queue — Cloud Run uses this for liveness checks
  2. App refuses to boot and logs a clear error message when any required environment variable is missing
  3. Repeated login attempts, rapid guest orders, and webhook floods are rate-limited and return 429 responses
  4. Unhandled exceptions appear in Sentry within seconds and structured JSON logs are queryable in Cloud Logging
  5. Every database query on tenant-scoped tables is confirmed scoped to shop_id — no cross-tenant data leakage possible

### Phase 8: CI/CD & Data Safety
**Goal**: Code pushed to main is automatically tested, built, and deployed to Cloud Run, with database backups ensuring data recovery
**Depends on**: Phase 7
**Requirements**: DEPLOY-04, SEC-04
**Plans:** 1/2 plans executed
Plans:
- [x] 08-01-PLAN.md — CI/CD pipeline: rewrite ci.yml with test gate + Docker build + Artifact Registry push + Cloud Run deploy + health check + auto-rollback (DEPLOY-04)
- [ ] 08-02-PLAN.md — GCP WIF setup, GitHub secrets configuration, pipeline verification, Cloud SQL backup enablement (DEPLOY-04, SEC-04)
**Success Criteria** (what must be TRUE):
  1. Pushing to main triggers a GitHub Actions workflow that runs the test suite, builds the Docker image, pushes to Artifact Registry, and deploys to Cloud Run
  2. A failed test suite prevents deployment — the pipeline stops and reports the failure
  3. Cloud SQL automated daily backups are enabled with a defined retention period and point-in-time recovery is available

### Phase 9: Production Activation & Gap Closure
**Goal**: All production services fully activated (database backups, structured logging, error tracking) and audit gaps from v1.2-MILESTONE-AUDIT.md closed
**Depends on**: Phase 8
**Requirements**: SEC-04
**Gap Closure**: Closes gaps from v1.2-MILESTONE-AUDIT.md
**Plans:** 2/2 plans complete
Plans:
- [x] 09-01-PLAN.md — AppServiceProvider DB_SOCKET validation fix, test coverage, stale ci.yml removal (HARD-02 gap, cleanup)
- [x] 09-02-PLAN.md — Cloud SQL backup enablement, GCS bucket setup, Cloud Run env var activation, Sentry DSN (SEC-04, HARD-04 activation)
**Success Criteria** (what must be TRUE):
  1. Cloud SQL automated daily backups enabled with 7-day retention and point-in-time recovery (SEC-04)
  2. LOG_CHANNEL=stackdriver set on Cloud Run — structured JSON logs with PII masking queryable in Cloud Logging (HARD-04 activation)
  3. Real SENTRY_LARAVEL_DSN configured on Cloud Run — unhandled exceptions appear in Sentry dashboard
  4. FILESYSTEM_DISK=gcs and LIVEWIRE_TEMP_DISK=gcs confirmed set on Cloud Run
  5. AppServiceProvider validates DB_SOCKET when DB_HOST is not explicitly set (HARD-02 gap fix)
  6. Stale bite/.github/workflows/ci.yml removed

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Polish | v1.0 | 3/3 | Complete | 2026-03-20 |
| 2. Demo | v1.0 | 2/2 | Complete | 2026-03-21 |
| 3. Item Availability | v1.1 | 2/2 | Complete | 2026-03-21 |
| 4. Image Optimization | v1.1 | 2/2 | Complete | 2026-03-21 |
| 5. Menu Themes | v1.1 | 2/2 | Complete | 2026-03-21 |
| 6. Containerization & Cloud Services | v1.2 | 2/2 | Complete | 2026-03-27 |
| 7. Hardening & Security | v1.2 | 3/3 | Complete | 2026-03-27 |
| 8. CI/CD & Data Safety | v1.2 | 2/2 | Complete | 2026-03-27 |
| 9. Production Activation & Gap Closure | v1.2 | 2/2 | Complete   | 2026-03-27 |

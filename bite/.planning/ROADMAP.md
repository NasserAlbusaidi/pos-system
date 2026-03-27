# Roadmap: Bite-POS

## Milestones

- ✅ **v1.0 Sourdough Demo** — Phases 1-2 (shipped 2026-03-21)
- ✅ **v1.1 Customization & Polish** — Phases 3-5 (shipped 2026-03-21)
- 🚧 **v1.2 Production Readiness** — Phases 6-8 (in progress)

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

- [ ] **Phase 6: Containerization & Cloud Services** - App runs in Docker with Cloud SQL and Cloud Storage, secrets managed via environment
- [ ] **Phase 7: Hardening & Security** - Production-grade health checks, rate limiting, observability, tenant isolation audit, and input validation
- [ ] **Phase 8: CI/CD & Data Safety** - Automated test-build-deploy pipeline and database backup strategy

## Phase Details

### Phase 6: Containerization & Cloud Services
**Goal**: App runs as a containerized service connected to managed cloud database and storage, with no hardcoded secrets
**Depends on**: Phase 5 (v1.1 complete)
**Requirements**: DEPLOY-01, DEPLOY-02, DEPLOY-03, SEC-02
**Plans:** 1/2 plans executed
Plans:
- [x] 06-01-PLAN.md — Production container with Nginx + PHP-FPM, Cloud SQL MySQL config, secrets enforcement
- [ ] 06-02-PLAN.md — GCS storage migration for product images, ImageService refactor, Livewire temp uploads
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
**Success Criteria** (what must be TRUE):
  1. GET /health returns status of DB connectivity, storage access, GD extension, and queue — Cloud Run uses this for liveness checks
  2. App refuses to boot and logs a clear error message when any required environment variable is missing
  3. Repeated login attempts, rapid guest orders, and webhook floods are rate-limited and return 429 responses
  4. Unhandled exceptions appear in Sentry within seconds and structured JSON logs are queryable in Cloud Logging
  5. Every database query on tenant-scoped tables is confirmed scoped to shop_id — no cross-tenant data leakage possible
**Plans**: TBD

### Phase 8: CI/CD & Data Safety
**Goal**: Code pushed to main is automatically tested, built, and deployed to Cloud Run, with database backups ensuring data recovery
**Depends on**: Phase 7
**Requirements**: DEPLOY-04, SEC-04
**Success Criteria** (what must be TRUE):
  1. Pushing to main triggers a GitHub Actions workflow that runs the test suite, builds the Docker image, pushes to Artifact Registry, and deploys to Cloud Run
  2. A failed test suite prevents deployment — the pipeline stops and reports the failure
  3. Cloud SQL automated daily backups are enabled with a defined retention period and point-in-time recovery is available
**Plans**: TBD

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Polish | v1.0 | 3/3 | Complete | 2026-03-20 |
| 2. Demo | v1.0 | 2/2 | Complete | 2026-03-21 |
| 3. Item Availability | v1.1 | 2/2 | Complete | 2026-03-21 |
| 4. Image Optimization | v1.1 | 2/2 | Complete | 2026-03-21 |
| 5. Menu Themes | v1.1 | 2/2 | Complete | 2026-03-21 |
| 6. Containerization & Cloud Services | v1.2 | 1/2 | In Progress|  |
| 7. Hardening & Security | v1.2 | 0/? | Not started | - |
| 8. CI/CD & Data Safety | v1.2 | 0/? | Not started | - |

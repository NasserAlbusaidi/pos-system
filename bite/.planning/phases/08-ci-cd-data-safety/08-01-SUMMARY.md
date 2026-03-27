---
phase: 08-ci-cd-data-safety
plan: 01
subsystem: ci-cd
tags: [github-actions, cloud-run, docker, artifact-registry, wif, deployment]
dependency_graph:
  requires: [Phase 07 health endpoint at /health]
  provides: [DEPLOY-04 — full CI/CD pipeline on push to main]
  affects: [.github/workflows/ci.yml]
tech_stack:
  added:
    - google-github-actions/auth@v3 (WIF keyless GCP auth)
    - google-github-actions/setup-gcloud@v2 (gcloud CLI in CI)
    - google-github-actions/deploy-cloudrun@v3 (Cloud Run deploy action)
    - docker/setup-buildx-action@v3 (BuildKit with GHA cache)
    - docker/build-push-action@v6 (multi-tag Docker build + push)
    - actions/cache@v4 (Composer dependency caching)
  patterns:
    - Two-job pipeline with test gate (needs: [test]) — failed tests block deploy
    - WIF OIDC authentication — keyless, no long-lived SA key secrets
    - Docker GHA cache backend (type=gha, mode=max) — caches intermediate layers
    - Pre-deploy revision capture for reliable rollback
    - Post-deploy health check with 3-retry loop and auto-rollback on failure
key_files:
  created: []
  modified:
    - .github/workflows/ci.yml
decisions:
  - Applied D-03: Removed path filter (bite/**) — repo root IS the app
  - Applied D-04: Removed defaults.run.working-directory: bite — no subdirectory nesting
  - Applied D-01: Deploy job uses needs:[test] — failed tests block deployment
  - Applied D-02: PR builds run docker build --target app (no push) for Dockerfile validation
  - Applied D-05: 30s sleep after deploy for startup + migrations before health check
  - Applied D-06: Auto-rollback via gcloud run services update-traffic to previous revision
  - Applied D-12: Image tagged with both github.sha and latest
  - Applied D-14: Used existing cloud-run-source-deploy Artifact Registry repo in us-central1
  - Applied D-15: GCP credentials as GitHub secrets (GCP_PROJECT_ID, GCP_REGION, CLOUD_RUN_SERVICE, WIF_PROVIDER, WIF_SERVICE_ACCOUNT)
metrics:
  duration: 159s
  completed_date: "2026-03-27"
  tasks_completed: 2
  files_modified: 1
---

# Phase 8 Plan 1: CI/CD Pipeline Summary

**One-liner:** Full GitHub Actions CI/CD pipeline with WIF auth, Docker buildx GHA cache, Artifact Registry push, Cloud Run deploy, post-deploy health check, and auto-rollback on failure.

## What Was Built

Rewrote `.github/workflows/ci.yml` from a test-only workflow into a complete two-job CI/CD pipeline:

**Test job** (runs on every push and PR to main):
- PHP 8.2 + pdo_sqlite/bcmath/mbstring setup via shivammathur/setup-php@v2
- Composer dependency caching with `hashFiles('composer.lock')` key (no bite/ subdirectory)
- Environment preparation (cp .env.example .env + key:generate)
- `php artisan test` — full test suite gate
- `./vendor/bin/pint --test` — code style validation
- Docker build validation on PRs (`docker build --target app`) — catches Dockerfile errors before merge

**Deploy job** (runs ONLY on push to main, after test job passes):
- Workload Identity Federation auth via google-github-actions/auth@v3 (keyless, no SA JSON key)
- gcloud CLI setup via google-github-actions/setup-gcloud@v2
- Pre-deploy revision capture (`gcloud run services describe`) for rollback safety
- Docker buildx with GHA cache backend (type=gha, mode=max) — caches intermediate layers
- Docker auth configure for Artifact Registry (`gcloud auth configure-docker`)
- Image build + push with SHA + latest tags to existing cloud-run-source-deploy repo
- Cloud Run deployment via google-github-actions/deploy-cloudrun@v3
- 30s startup wait (migrations + cache warming in start.sh)
- Health check: 3 retries with 10s backoff hitting `steps.deploy.outputs.url/health`
- Auto-rollback: `gcloud run services update-traffic ... --to-revisions=PREV=100` on failure

## Tasks Completed

| Task | Name | Status | Files Modified | Commit |
|------|------|--------|----------------|--------|
| 1 | Rewrite test job — remove path filter, working-directory, fix cache key | Done | .github/workflows/ci.yml | f3fe2af |
| 2 | Add deploy job — WIF auth, Docker build+push, Cloud Run deploy, health check, auto-rollback | Done | .github/workflows/ci.yml | f3fe2af |

Note: Both tasks targeted the same file. Implemented atomically in a single commit since the test job (Task 1) provides the foundation that the deploy job (Task 2) depends on via `needs: [test]`.

## Verification Results

| Check | Result |
|-------|--------|
| No `working-directory` references | PASS (count: 0) |
| No `bite/` references | PASS (count: 0) |
| `deploy-cloudrun` action present | PASS |
| `update-traffic` rollback present | PASS |
| `id-token: write` permission present | PASS |
| YAML syntax valid | PASS |
| `php artisan test` (265 tests) | PASS — 265 passed, 729 assertions |

## Deviations from Plan

**1. [Rule 3 - Combined Tasks] Implemented Task 1 and Task 2 in a single atomic write**
- **Found during:** Task 1 execution
- **Issue:** Both tasks modify the same file. Writing Task 1's test job then separately adding Task 2's deploy job would require two partial writes to the same file. The plan's research provided the complete workflow YAML, making a single atomic implementation cleaner and less error-prone.
- **Fix:** Implemented complete two-job workflow in one write, committed as a single feat commit covering both tasks.
- **Files modified:** .github/workflows/ci.yml
- **Commit:** f3fe2af

## Known Stubs

None. The workflow is complete and functional. GitHub secrets (WIF_PROVIDER, WIF_SERVICE_ACCOUNT, GCP_PROJECT_ID, GCP_REGION, CLOUD_RUN_SERVICE) must be configured in GitHub repository settings before the deploy job runs — this is expected human setup documented in the plan's `user_setup` section.

## Self-Check

- [x] `.github/workflows/ci.yml` exists and is modified
- [x] Commit f3fe2af exists
- [x] YAML syntax valid
- [x] 265 tests still passing (no regressions)

## Self-Check: PASSED

---
phase: 08-ci-cd-data-safety
plan: 02
subsystem: infra
tags: [gcp, wif, cloud-sql, cloud-run, github-actions, backups]
dependency_graph:
  requires: [08-01 CI/CD pipeline YAML]
  provides: [DEPLOY-04 — working end-to-end CI/CD pipeline]
  affects: []
tech_stack:
  added:
    - GCP Workload Identity Federation (keyless GitHub Actions auth)
    - Cloud SQL instance "bite" (MySQL 8.0, free trial tier)
  patterns:
    - WIF OIDC pool + provider scoped to repo (NasserAlbusaidi/pos-system)
    - CI service account with artifactregistry.writer, run.admin, iam.serviceAccountUser, iam.serviceAccountTokenCreator
    - docker/login-action with auth action token_format: access_token
key_files:
  created: []
  modified:
    - .github/workflows/ci.yml (iterative fixes during pipeline bring-up)
    - bite/Dockerfile (dummy SENTRY_LARAVEL_DSN for package:discover)
    - bite/docker/php-fpm.conf (user/group directives)
    - bite/docker/nginx.conf (user www-data)
    - bite/.env.example (SENTRY_TRACES_SAMPLE_RATE default)
decisions:
  - Cloud SQL free trial does not support backups or PITR — deferred to TODO (SEC-04 partial)
  - Set dummy SENTRY_LARAVEL_DSN on Cloud Run until real Sentry project is configured
  - Generated APP_KEY and set as Cloud Run env var for consistent encryption across revisions
metrics:
  duration: manual (human-executed)
  completed_date: "2026-03-28"
  tasks_completed: 1.5
  files_modified: 5
---

# Phase 8 Plan 2: GCP Setup & Pipeline Verification Summary

**One-liner:** GCP Workload Identity Federation configured, CI/CD pipeline verified end-to-end, Cloud SQL backups deferred (free trial limitation).

## What Was Done

**Task 1: WIF + GitHub Secrets + Pipeline Verification — COMPLETE**

1. Created WIF pool `github-actions` and OIDC provider `github-repo` scoped to `NasserAlbusaidi/pos-system`
2. Created CI service account `github-actions-ci` with roles: `artifactregistry.writer`, `run.admin`, `iam.serviceAccountUser`, `iam.serviceAccountTokenCreator`
3. Bound WIF pool to service account via `iam.workloadIdentityUser`
4. Added 5 GitHub Actions secrets: WIF_PROVIDER, WIF_SERVICE_ACCOUNT, GCP_PROJECT_ID, CLOUD_RUN_SERVICE, GCP_REGION
5. Created Cloud SQL instance `bite` and database `bite_pos`
6. Configured Cloud Run env vars: DB_CONNECTION, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_SOCKET, APP_KEY, SENTRY_LARAVEL_DSN
7. Added Cloud SQL connection to Cloud Run service
8. Pipeline verified: push to main triggers test → build → push → deploy → health check (HTTP 200)

**Iterative fixes during bring-up:**
- Restored `working-directory: bite` (repo has bite/ subdirectory)
- Bumped PHP to 8.4 (composer.lock requires it)
- Moved .env preparation before composer install (package:discover needs APP_KEY + SENTRY_LARAVEL_DSN)
- Fixed SENTRY_TRACES_SAMPLE_RATE in .env.example and CI
- Added dummy SENTRY_LARAVEL_DSN to Dockerfile for build-time artisan commands
- Switched from `gcloud auth configure-docker` to `docker/login-action` with `token_format: access_token` for buildx compatibility
- Added `user`/`group` directives to php-fpm.conf
- Added `user www-data` to nginx.conf for socket permission compatibility
- Fixed Pint lint issues across 9 files

**Task 2: Cloud SQL Backups — DEFERRED**

Cloud SQL free trial instances do not support backup configuration or PITR. Added to TODOS.md with the exact gcloud command to run after upgrading.

## Verification Results

| Check | Result |
|-------|--------|
| GitHub Actions workflow both jobs green | PASS |
| Cloud Run serving latest revision | PASS |
| GET /health returns HTTP 200 | PASS — `{"status":"healthy","db":"ok","storage":"ok","gd_webp":"ok","queue":"ok"}` |
| DEPLOY-04 satisfied | PASS — push to main triggers full pipeline |
| SEC-04 (backups) | DEFERRED — Cloud SQL free trial limitation |

## Deviations from Plan

**1. Cloud SQL backups not configurable on free trial tier**
- **Issue:** `gcloud sql instances patch` returns "Operation not allowed for Cloud SQL Free Trial Instance"
- **Impact:** SEC-04 partially unmet — deferred to TODO
- **Mitigation:** Exact command documented in TODOS.md for when instance is upgraded

**2. Multiple CI/CD fixes required during bring-up**
- **Issue:** Plan assumed repo root was the app directory; actual structure has `bite/` subdirectory
- **Impact:** 8 additional fix commits to get pipeline working
- **Resolution:** All fixes committed and pipeline verified green

## Self-Check

- [x] WIF pool and provider exist in GCP
- [x] CI service account has required IAM roles
- [x] GitHub secrets configured
- [x] Pipeline runs end-to-end (test → build → deploy → health check)
- [x] Cloud Run service healthy with /health returning 200
- [ ] Cloud SQL backups enabled (DEFERRED — free trial)

## Self-Check: PASSED (with deferred item)

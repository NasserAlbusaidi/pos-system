# Phase 8: CI/CD & Data Safety - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-27
**Phase:** 08-ci-cd-data-safety
**Areas discussed:** Deployment trigger & gating, Post-deploy verification, Backup & recovery policy, Image tagging & rollback

---

## Deployment Trigger & Gating

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-deploy on push to main | Tests pass -> build -> push -> deploy. Fastest iteration for solo founder. Cloud Run keeps previous revision. | ✓ |
| Manual approval gate | Tests pass -> build -> WAIT for manual approval -> deploy. Safer but adds friction. | |
| PR merge triggers deploy | Only PR merges trigger deploy, not direct pushes. Enforces code review via branch protection. | |

**User's choice:** Auto-deploy on push to main
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Test + build on PR, deploy on merge | PRs run tests + lint + Docker build (no push/deploy). Catches build failures before merge. | ✓ |
| PRs only run tests + lint | Keep PR checks fast. Docker build only on push to main. | |

**User's choice:** Test + build on PR, deploy on merge
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Remove path filter | Any push to main triggers pipeline. Simpler, catches all config changes. | ✓ |
| Keep bite/** filter | Only bite/ changes trigger pipeline. Risky — misses Dockerfile changes. | |

**User's choice:** Remove path filter
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| bite is a subdirectory | Repo contains other projects. Keep working-directory: bite. | |
| bite IS the repo root | Repo root is the app. Remove working-directory: bite. | ✓ |

**User's choice:** bite IS the repo root
**Notes:** None

---

## Post-Deploy Verification

| Option | Description | Selected |
|--------|-------------|----------|
| Pipeline hits /health after deploy | Wait ~30s then GET /health. If unhealthy, roll back. | ✓ |
| Trust Cloud Run's built-in checks | Cloud Run uses /health as startup probe. No extra pipeline step. | |
| Both — belt and suspenders | Cloud Run handles startup + pipeline does smoke test. Most robust. | |

**User's choice:** Pipeline hits /health after deploy
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-rollback to previous revision | Pipeline shifts traffic back if health check fails. Production stays safe. | ✓ |
| Alert only — manual rollback | Pipeline fails with error. User decides whether to rollback. | |

**User's choice:** Auto-rollback to previous revision
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| GitHub Actions email only | Built-in email on workflow failure. Simple for solo founder. | ✓ |
| Add a notification channel | Slack, Discord, or custom webhook. More immediate than email. | |

**User's choice:** GitHub Actions email only
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Container startup handles migrations | start.sh already runs migrate --force. Simple, already working. | ✓ |
| Pipeline runs migrations before deploy | Separates migration from deployment. Requires DB access from GitHub Actions. | |

**User's choice:** Container startup handles migrations
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| 30 seconds | Cloud Run starts containers in 10-20s. 30s gives margin for migrations + caching. | ✓ |
| 60 seconds | Extra margin for safety. | |
| You decide | Claude picks based on startup complexity. | |

**User's choice:** 30 seconds
**Notes:** None

---

## Backup & Recovery Policy

| Option | Description | Selected |
|--------|-------------|----------|
| 7 days | Standard for small SaaS. Full week of recovery. Minimal cost. | ✓ |
| 14 days | Two-week window. More margin for slow-burn issues. | |
| 30 days | Full month. Maximum safety, higher storage cost. | |

**User's choice:** 7 days
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Enable PITR | Restore to any specific second. Critical for POS with real orders. | ✓ |
| Daily snapshots only | Restore to most recent daily backup. Could lose up to 24h of orders. | |

**User's choice:** Enable PITR
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Capture in CONTEXT.md only | Planner documents gcloud commands. No separate runbook. | ✓ |
| Create a backup runbook | Separate docs/backup-runbook.md with procedures. More formal. | |

**User's choice:** Capture in CONTEXT.md only
**Notes:** None

---

## Image Tagging & Rollback

| Option | Description | Selected |
|--------|-------------|----------|
| Commit SHA + latest | Tag as bite-pos:<sha> AND bite-pos:latest. SHA for traceability, latest for simplicity. | ✓ |
| Commit SHA only | No latest tag. Every deploy references exact SHA. | |
| Semver from VERSION file | Tag with version like 1.2.3. Requires VERSION file maintenance. | |

**User's choice:** Commit SHA + latest
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Keep all images | Storage is cheap. Every image available for rollback. | ✓ |
| Auto-delete older than 30 days | Cleanup policy. Limits rollback window. | |
| You decide | Claude picks sensible cleanup. | |

**User's choice:** Keep all images
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Use existing repo | cloud-run-source-deploy in us-central1. No extra setup. | ✓ |
| Create dedicated bite-pos repo | Cleaner separation. New repo in us-central1. | |

**User's choice:** Use existing repo
**Notes:** None

---

### GCP Config (discovered via CLI)
- Project ID: `ascent-web-260224-119`
- Cloud Run service: `bite-pos-demo`
- Region: `us-central1`
- Artifact Registry: `cloud-run-source-deploy` (us-central1)
- User wants GCP credentials stored as GitHub secrets

## Claude's Discretion

- GitHub Actions GCP authentication method (Workload Identity Federation vs service account key)
- Docker build caching strategy in CI
- Health check retry logic (retries, backoff)
- Cloud SQL backup window timing
- Artifact Registry image path format

## Deferred Ideas

None — discussion stayed within phase scope

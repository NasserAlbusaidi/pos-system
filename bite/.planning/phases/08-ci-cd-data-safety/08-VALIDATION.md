---
phase: 8
slug: ci-cd-data-safety
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-27
---

# Phase 8 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit via Laravel `php artisan test` |
| **Config file** | `phpunit.xml` (SQLite in-memory for tests) |
| **Quick run command** | `php artisan test --filter=HealthCheckTest` |
| **Full suite command** | `composer test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test`
- **After every plan wave:** Run `composer test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 15 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 08-01-01 | 01 | 1 | DEPLOY-04 | unit/integration | `php artisan test` | ✅ existing suite | ⬜ pending |
| 08-01-02 | 01 | 1 | DEPLOY-04 | smoke | Push PR, verify Actions run test+lint+build only | Manual only | ⬜ pending |
| 08-01-03 | 01 | 1 | DEPLOY-04 | smoke | Push to main, verify deploy job triggers | Manual only | ⬜ pending |
| 08-01-04 | 01 | 1 | DEPLOY-04 | smoke | Force test failure, verify deploy blocked | Manual only | ⬜ pending |
| 08-01-05 | 01 | 1 | DEPLOY-04 | smoke | `curl -s $CLOUD_RUN_URL/health` returns 200 | Manual only | ⬜ pending |
| 08-02-01 | 02 | 1 | SEC-04 | smoke | `gcloud sql instances describe INSTANCE --format="yaml(settings.backupConfiguration)"` → `enabled: true, binaryLogEnabled: true` | Manual only | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

None — existing test infrastructure covers all phase requirements. The new infrastructure work (workflow YAML, gcloud commands) cannot be unit tested; it is verified by execution.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| PR triggers test+lint+Docker build only | DEPLOY-04 | GitHub Actions workflow behavior — cannot unit test YAML | Push a test PR, verify Actions run, check no image pushed to Artifact Registry |
| Push to main triggers full pipeline | DEPLOY-04 | Live pipeline behavior | Merge PR or push directly to main, verify deploy job runs |
| Failed tests block deploy | DEPLOY-04 | Pipeline gating logic | Introduce intentional test failure, push to main, verify deploy job is skipped |
| Post-deploy health check passes | DEPLOY-04 | Live system verification | After deploy, `curl` the health endpoint and verify 200 response |
| Auto-rollback on health failure | DEPLOY-04 | Destructive test — would require intentionally breaking health check | Verify rollback step exists in workflow YAML with correct conditional |
| Cloud SQL backups enabled | SEC-04 | GCP infrastructure configuration | Run `gcloud sql instances describe` and verify backup settings |
| PITR enabled (binary logging) | SEC-04 | GCP infrastructure configuration | Verify `binaryLogEnabled: true` in describe output |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 15s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending

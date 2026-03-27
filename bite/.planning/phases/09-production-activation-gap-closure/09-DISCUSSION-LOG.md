# Phase 9: Production Activation & Gap Closure - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-28
**Phase:** 09-production-activation-gap-closure
**Areas discussed:** GCS bucket identity, Sentry project setup, Env var rollout strategy, Backup timing

---

## GCS Bucket Identity

### Q1: Do you have an existing GCS bucket for Bite-POS product images?

| Option | Description | Selected |
|--------|-------------|----------|
| No bucket yet | Need to create a new GCS bucket during this phase | |
| I have one already | I know the bucket name — let me provide it | |
| Not sure | Need to check GCP console first. Plan should include a verification step | ✓ |

**User's choice:** Not sure
**Notes:** Research found 4 buckets in GCP project but none appears to be an app storage bucket.

### Q2: If no bucket exists, should the plan create one? Naming convention?

| Option | Description | Selected |
|--------|-------------|----------|
| Create if needed: bite-pos-storage | Plan checks first, creates in us-central1 if none found | ✓ |
| Create if needed: bite-pos-uploads | Separate name to distinguish from other buckets | |
| Skip GCS activation | Defer FILESYSTEM_DISK=gcs to a later step | |

**User's choice:** Create if needed: bite-pos-storage

---

## Sentry Project Setup

### Q1: Do you already have a Sentry account or project for Bite-POS?

| Option | Description | Selected |
|--------|-------------|----------|
| No Sentry account yet | Plan includes manual step to create free account + project | ✓ |
| Have account, no project | Plan prompts to create Laravel project in Sentry | |
| Have DSN ready | Already have a Sentry DSN — just set it on Cloud Run | |

**User's choice:** No Sentry account yet

### Q2: Should the plan block on Sentry DSN, or set other env vars first?

| Option | Description | Selected |
|--------|-------------|----------|
| Block on Sentry DSN | Set all env vars in one update after creating Sentry account | ✓ |
| Set others first, Sentry later | Deploy other vars now, update Sentry DSN separately | |

**User's choice:** Block on Sentry DSN

---

## Env Var Rollout Strategy

| Option | Description | Selected |
|--------|-------------|----------|
| All at once (Recommended) | Single gcloud update, one revision, one health check | ✓ |
| Incremental (2 steps) | Split into logging/storage vars then Sentry, two revisions | |
| One var at a time | Each var separately, most conservative but 6 revisions | |

**User's choice:** All at once
**Notes:** Auto-rollback from Phase 8 CI/CD pipeline provides safety net.

---

## Backup Timing

| Option | Description | Selected |
|--------|-------------|----------|
| Run anytime (Recommended) | Service isn't live — no user impact from brief restart | ✓ |
| Schedule for 02:00 UTC | Run during lowest traffic window (06:00 Oman time) | |
| You decide | Claude picks timing based on deployment context | |

**User's choice:** Run anytime
**Notes:** Sourdough Oman hasn't launched yet, no real customer traffic.

---

## Claude's Discretion

- GCS bucket creation flags (storage class, location, uniform access)
- GCS bucket IAM permissions setup
- Post-update verification sequence
- DB_SOCKET validation implementation approach
- StartupValidationTest new test case structure

## Deferred Ideas

None — discussion stayed within phase scope

# Phase 6: Containerization & Cloud Services - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-27
**Phase:** 06-containerization-cloud-services
**Areas discussed:** Container architecture, Database strategy, File storage migration, Secrets management

---

## Container Architecture

| Option | Description | Selected |
|--------|-------------|----------|
| Nginx + PHP-FPM | Production-standard setup. Nginx serves static files and proxies PHP to FPM. Handles concurrency properly. | ✓ |
| FrankenPHP | Modern single-binary PHP app server. Simpler Dockerfile, built-in worker mode. Newer. | |
| Keep artisan serve | It works for now. Defer upgrade until concurrency issues. | |

**User's choice:** Nginx + PHP-FPM (Recommended)
**Notes:** Current setup is single-threaded and not production-grade. User also reported Snap-to-Menu upload failures in production which were fixed by adding PHP CLI upload limit flags — properly handled by Nginx + FPM config.

---

## Database Strategy — Connection

| Option | Description | Selected |
|--------|-------------|----------|
| Cloud SQL Auth Proxy sidecar | Cloud Run built-in connection support. Unix socket, no public IP. Secure by default. | ✓ |
| Private IP via VPC | Connect over private network. Requires VPC connector setup. More complex. | |
| Public IP with SSL | Simplest but requires managing SSL certs and IP allowlisting. Least secure. | |

**User's choice:** Cloud SQL Auth Proxy sidecar (Recommended)
**Notes:** None

## Database Strategy — Demo Data

| Option | Description | Selected |
|--------|-------------|----------|
| Seed on first deploy only | Run migrations + seed once when setting up Cloud SQL. After that, migrations only. Demo shop persists. | ✓ |
| Separate demo instance | Keep a separate Cloud Run service with SQLite for demos. Production uses Cloud SQL. | |
| Seed flag in deploy | SEED_ON_DEPLOY env var. Useful for staging but risky for production. | |

**User's choice:** Seed on first deploy only (Recommended)
**Notes:** None

---

## File Storage Migration — Image URLs

| Option | Description | Selected |
|--------|-------------|----------|
| GCS public URLs | Make bucket publicly readable. Direct storage.googleapis.com URLs. Simplest, fastest. | ✓ |
| Signed URLs | Private bucket, time-limited signed URLs. More secure but adds latency. | |
| Proxy through Laravel | Laravel streams from GCS. Most control but worst performance. | |

**User's choice:** GCS public URLs (Recommended)
**Notes:** Product photos are not sensitive data — public bucket is appropriate.

## File Storage Migration — Livewire Temp Uploads

| Option | Description | Selected |
|--------|-------------|----------|
| GCS for Livewire temp too | Configure Livewire temp upload disk to GCS. Multi-instance safe. | ✓ |
| Session affinity + local temp | Cloud Run session affinity keeps user on same instance. Fragile at scale. | |
| Process inline, skip temp | Read upload into memory and process immediately. Only works for small files. | |

**User's choice:** GCS for Livewire temp too (Recommended)
**Notes:** Resolves the Snap-to-Menu upload issue fully for multi-instance deployments.

---

## Secrets Management

| Option | Description | Selected |
|--------|-------------|----------|
| Cloud Run env vars | Set secrets as env vars in Cloud Run config. Built-in Secret Manager integration. | ✓ |
| Secret Manager with SDK | Fetch secrets at boot via SDK. More secure rotation but adds dependency. | |
| Doppler / external vault | Third-party secrets manager. Overkill for single service. | |

**User's choice:** Cloud Run env vars (Recommended)
**Notes:** None

---

## Claude's Discretion

- Supervisor/multi-process approach for Nginx + FPM in single container
- Nginx configuration details
- GCS bucket naming and region
- Migration script for SQLite to Cloud SQL transition

## Deferred Ideas

None — discussion stayed within phase scope

# Bite Operations Guide

Operational runbook for Bite-POS in production. For the initial provisioning and deploy pipeline, see `DEPLOYMENT.md`. For architectural invariants, see `ARCHITECTURE.md`.

## Topology

- **Cloud Run** — single container (Nginx + PHP-FPM + supervisord), autoscaled. Health probe: `GET /health`.
- **Cloud SQL MySQL 8.0** — instance `bite`, reached via Auth Proxy Unix socket (`DB_SOCKET=/cloudsql/...`).
- **Cloud Storage** — bucket `bite-pos-storage` for product images, Livewire temp uploads, Snap-to-Menu source photos.
- **Sentry** — error capture + 10% trace sampling (`SENTRY_TRACES_SAMPLE_RATE=0.10`).
- **Stackdriver / Cloud Logging** — structured JSON logs via the `stackdriver` log channel, with PII masked by `PiiMaskingProcessor` (phone, email, last two IP octets).
- **GitHub Actions** — CI + CD; see `.github/workflows/ci.yml`.

## Required Env Vars

Canonical list: see `DEPLOYMENT.md § Runtime Configuration`. Rules of thumb in production:

- Always use `config()` in code, never `env()` — `config:cache` makes `env()` return `null`.
- GCS env vars are only required when `filesystems.default=gcs`.
- `DB_SOCKET` is set in production (Cloud SQL Auth Proxy); `DB_HOST` is used locally. Startup validation picks the right one.

## Deploy Order

Pushes to `main` trigger the full pipeline automatically. The workflow:

1. Runs tests + Pint lint on SQLite in-memory.
2. Captures the current live Cloud Run revision for rollback.
3. Builds and pushes the container image to Artifact Registry (with GHA layer cache).
4. Deploys to Cloud Run.
5. Waits 30s, then polls `/health` up to 3× with 10s backoff.
6. On health-check failure, shifts 100% traffic back to the captured revision.

Manual deploys are almost never needed. If required:

```bash
gcloud run deploy bite-pos-demo \
  --image us-central1-docker.pkg.dev/<project>/cloud-run-source-deploy/bite-pos-demo:<tag> \
  --region us-central1
```

## Health Check

`GET /health` (`HealthController`) returns 200 only when **all** of the following pass:

- Database connection works (`PDO::query('SELECT 1')`)
- Default storage disk is writable (a tiny probe file is written + deleted)
- GD extension is loaded with WebP support
- Queue connection resolves

Uptime monitoring (UptimeRobot / Better Stack) should hit `/health`, not `/`.

## Rollback

**Automatic** on post-deploy health-check failure — the workflow shifts traffic back.

**Manual:**

```bash
gcloud run revisions list --service=bite-pos-demo --region=us-central1
gcloud run services update-traffic bite-pos-demo \
  --region=us-central1 --to-revisions=<previous>=100
```

Never delete the prior revision immediately — keep at least two revisions around for rollback.

## Migrations

Migrations run on container start via `docker/start.sh` (`php artisan migrate --force`). Rules:

- Additive migrations only on `main` — never edit an existing migration file.
- For destructive changes (drop column, change type), ship a two-phase migration: deploy the additive change first, backfill, then deploy the removal.
- Key migrations to understand: `orders.tracking_token`, `orders.fulfilled_at`, `webhook_events`.

## Scheduler & Queue

- **Scheduler:** Cloud Scheduler hits an internal endpoint (or a sidecar) to trigger `php artisan schedule:run` every minute. Scheduled tasks include `Order::cancelExpired()` (see `routes/console.php`).
- **Queue:** `QUEUE_CONNECTION=sync` today — jobs run inline. When switching to `database` or `redis`, run a dedicated Cloud Run Job worker; do not try to run a long-lived queue worker inside the web container.

## Stripe Webhooks

Endpoints:
- `POST /webhooks/stripe` — payment events
- `POST /webhooks/stripe/subscription` — Cashier subscription lifecycle

Invariants:
- Signature verification is required. Invalid signatures return `400`.
- Duplicate event IDs are absorbed idempotently via `webhook_events(provider,event_id)`.
- Payment mutations lock the target order row before writing.

Monitor:
- Signature-verification failure rate (indicates misconfigured `STRIPE_WEBHOOK_SECRET` or a spoofing attempt).
- Duplicate event spikes (Stripe retries; expected in small volumes, alarming in large ones).
- `webhook_events` table growth — prune events older than 30 days if needed.

**Note:** Thawani Pay is planned for production but not yet integrated. When it ships, add a `thawani` provider to `webhook_events` and keep Stripe's subscription endpoint.

## Rate Limiting

- **Webhooks:** 60 req/min per IP
- **Guest ordering** (`/menu/...` cart + place-order): 10 req / 15 min per IP
- **Login + PIN login:** 5 req/min per IP+identifier

Spikes in rate-limit 429s are a signal — either a real attack, a misbehaving client, or a script hitting the guest order endpoint.

## Observability

### Sentry
- Errors captured automatically from Laravel exception handler.
- 10% of requests sampled for performance traces (`SENTRY_TRACES_SAMPLE_RATE=0.10`).
- Tune the release name in `SENTRY_RELEASE` via CI if version tagging matters.

### Cloud Logging (`stackdriver` channel)
- Structured JSON log entries. Default level is `error` to keep Cloud Logging cost down.
- `PiiMaskingProcessor` redacts phone numbers, email addresses, and the last two octets of any IP (e.g. `192.168.***`).
- `SlowRequestMiddleware` logs any request that exceeds 2s as a warning.

### Useful log queries (Cloud Logging)
- Failed deploys: `severity=ERROR AND resource.type="cloud_run_revision"`
- Slow requests: `jsonPayload.event="slow_request"`
- Webhook failures: `jsonPayload.channel="webhook" AND severity>=ERROR`

## Storage (GCS)

- Bucket: `bite-pos-storage` in `us-central1`.
- `ImageService` writes three WebP variants per upload: `thumb` (200px), `card` (400px), `full` (800px). All streams — never `file_put_contents`.
- Livewire temp uploads also go to GCS; the OnboardingWizard has a fallback to local disk when GCS returns an empty read (see `aa9843e`).
- **Orphan cleanup:** `ProductManager` currently doesn't delete the old image when a product photo is replaced — tracked in `TODOS.md`.

## Cloud SQL

- **Backups + PITR** are disabled because the instance is on GCP Free Trial. Enable after upgrading off the free trial (command in `TODOS.md` SEC-04).
- Connection is exclusively via the Cloud SQL Auth Proxy socket — never expose a public IP.

## Security Monitoring

Watch for:
- PIN / manager-override throttle spikes (`login_throttle` events)
- 403 RBAC denials on privileged routes — scope to `manager|admin` paths
- Inventory anomalies after `completed` transitions (double-fulfillment attempts)
- Signature-verification failures on Stripe webhook
- Requests to `/livewire/*` returning as static files (means Nginx config regression — the `^~` prefix is required)

## Operational Checklists

### After a deploy
- [ ] `/health` green in Cloud Run
- [ ] No new Sentry issues in the last 15 min
- [ ] Stripe test webhook delivers and is idempotent
- [ ] Guest menu loads images from `storage.googleapis.com/...`

### Weekly
- [ ] Review Sentry top issues
- [ ] Check `failed_jobs` and `webhook_events` table sizes
- [ ] Confirm Cloud SQL storage utilization < 80%
- [ ] Verify Cloud Run traffic is on the expected revision

### Before rolling back
- [ ] Capture the broken revision's logs (`gcloud run revisions logs`)
- [ ] File a Sentry issue or bug ticket
- [ ] Confirm the target revision's env vars are still compatible with current DB schema

## Useful Commands

```bash
# Tail Cloud Run logs
gcloud logging read 'resource.type="cloud_run_revision" AND resource.labels.service_name="bite-pos-demo"' --limit=50 --format=json

# Describe the live revision
gcloud run services describe bite-pos-demo --region=us-central1

# Check Cloud SQL status
gcloud sql instances describe bite

# Local dev sanity
php artisan route:list -v
php artisan test
./vendor/bin/pint --test
npm run build
```

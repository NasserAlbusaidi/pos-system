# Bite-POS Cloud Run Deployment Reference (Paused)

> **Current pilot target is Laravel Forge, not Cloud Run.** The Sourdough pilot
> deploys to a single Forge VPS with local-disk images — see
> [`DEPLOYMENT-FORGE.md`](./DEPLOYMENT-FORGE.md). The Cloud Run path below is
> paused but kept for reference / future multi-tenant scale-out. Do not use this
> guide for the active restaurant handoff.

If this path is revived, production would run on **Google Cloud Run** (single
container: Nginx + PHP-FPM + supervisord), with **Cloud SQL (MySQL 8.0)** for
data and **Google Cloud Storage** for product images and Livewire uploads. CI/CD
would be handled by GitHub Actions with Workload Identity Federation — there are
no long-lived service-account keys.

## Infrastructure

| Component | Service | Notes |
|-----------|---------|-------|
| Runtime | Cloud Run | Region `us-central1`, single container image, autoscaling |
| Registry | Artifact Registry | `us-central1-docker.pkg.dev/<project>/cloud-run-source-deploy/bite-pos-demo` |
| Database | Cloud SQL MySQL 8.0 | Instance `bite`, connected via Cloud SQL Auth Proxy Unix socket |
| Storage | Cloud Storage | Bucket `bite-pos-storage` (`us-central1`), `spatie/laravel-google-cloud-storage` driver |
| CI/CD | GitHub Actions | Workflow at `.github/workflows/ci.yml`, WIF auth, pre-deploy revision capture for rollback |
| Errors | Sentry | `SENTRY_LARAVEL_DSN` + `SENTRY_TRACES_SAMPLE_RATE=0.1` |
| Mail | Resend | `MAIL_MAILER=resend` in production |
| SSL / TLS | Cloud Run managed | Automatic HTTPS on the Cloud Run URL; custom domain via domain mapping |

## Container Shape

`Dockerfile` is a multi-stage build:

1. **`frontend` stage** — `node:22-alpine` runs `npm ci && npm run build`, producing the Vite bundle at `public/build/`.
2. **`app` stage** — `php:8.4-fpm-bookworm` installs Nginx, supervisord, and the GD extension compiled with FreeType / JPEG / WebP. Composer deps are installed with `--no-dev --optimize-autoloader`. Nginx, PHP-FPM, and supervisord configs live in `docker/` and are copied in at build time.

Key container rules (learned the hard way):

- **Single process group only.** Cloud Run kills the container if the root process exits, so supervisord runs Nginx and PHP-FPM as children.
- **`clear_env=no` in PHP-FPM pool** (`docker/php-fpm.conf`). Without it, Cloud Run env vars never reach PHP worker processes.
- **Nginx runs as `www-data`** to match the PHP-FPM socket permissions.
- **Build-time dummy secrets.** `APP_KEY` and `SENTRY_LARAVEL_DSN` are set to throwaway values during `composer install` / `package:discover` so the build doesn't crash. Real secrets are injected at runtime via Cloud Run env vars.
- **Static assets vs `/livewire`.** The Nginx config uses `location ^~ /livewire` to override the static-asset regex; without the prefix, Livewire AJAX requests get served as static files and 404.

## CI/CD Pipeline (`.github/workflows/ci.yml`)

Triggered on push and PR to `main`. Two jobs:

### `test`
- PHP 8.4 on Ubuntu, SQLite in-memory tests
- Composer cache keyed on `composer.lock`
- Runs `php artisan test` + `./vendor/bin/pint --test`
- On PR, also runs `docker build --target app` to validate the container

### `deploy` (only on push to `main`)
- Authenticates to GCP via Workload Identity Federation (`google-github-actions/auth@v3`, `token_format: access_token`)
- Captures the current live Cloud Run revision for rollback before deploying
- Builds and pushes the Docker image to Artifact Registry with GitHub Actions cache (`type=gha,mode=max`)
- Deploys to Cloud Run
- Waits 30s for cold start, then hits `/health` up to 3 times with 10s backoff
- On failure, rolls traffic back to the captured revision

### Required GitHub Secrets

| Secret | Purpose |
|--------|---------|
| `GCP_PROJECT_ID` | Project containing Cloud Run + Cloud SQL |
| `GCP_REGION` | Cloud Run region (`us-central1`) |
| `CLOUD_RUN_SERVICE` | Service name (e.g. `bite-pos-demo`) |
| `WIF_PROVIDER` | Full WIF provider resource name |
| `WIF_SERVICE_ACCOUNT` | Email of the deploy service account |

## Runtime Configuration

All secrets and per-environment config are set as **Cloud Run env vars**, never in code. Use `config()` everywhere in production — `env()` returns `null` after `config:cache`.

### Required env vars

```
APP_NAME="Bite POS"
APP_ENV=production
APP_KEY=base64:GENERATED_WITH_php_artisan_key_generate_show
APP_DEBUG=false
APP_URL=https://<your-domain>
APP_KEY=base64:...

# Database (Cloud SQL Auth Proxy socket)
DB_CONNECTION=mysql
DB_SOCKET=/cloudsql/<project>:<region>:bite
DB_DATABASE=bite_pos
DB_USERNAME=bite
DB_PASSWORD=...

# Storage (GCS)
FILESYSTEM_DISK=gcs
GOOGLE_CLOUD_PROJECT_ID=<project>
GOOGLE_CLOUD_STORAGE_BUCKET=bite-pos-storage
GOOGLE_CLOUD_STORAGE_API_URI=https://storage.googleapis.com

# Queue / cache / session
QUEUE_CONNECTION=sync       # no worker yet; upgrade to database if needed
CACHE_STORE=file
SESSION_DRIVER=file

# Observability
LOG_CHANNEL=stackdriver
SENTRY_LARAVEL_DSN=https://...
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.10

# Mail
MAIL_MAILER=resend
RESEND_KEY=re_...
MAIL_FROM_ADDRESS=noreply@<your-domain>
MAIL_FROM_NAME="Bite POS"

# Stripe (payments + subscriptions)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SUBSCRIPTION_WEBHOOK_SECRET=whsec_...
CASHIER_CURRENCY=omr
CASHIER_CURRENCY_LOCALE=en_OM
STRIPE_FREE_PRICE_ID=price_...
STRIPE_PRO_PRICE_ID=price_...

# AI (Snap-to-Menu)
GEMINI_API_KEY=...

# Proxy trust (Cloud Run front-end)
TRUSTED_PROXIES=*
FORCE_HTTPS=true
```

### Required `APP_KEY`
`APP_KEY` is a required production secret. Generate it once, store it in the deployment secret store, and keep the same value across every app instance and revision:

```bash
php artisan key:generate --show
```

For Cloud Run, store the generated value in Secret Manager and expose it as the `APP_KEY` environment variable. Pin a specific secret version; do not reference `latest` because APP_KEY rotation is destructive.

```bash
PROJECT_ID="$(gcloud config get-value project)"
RUNTIME_SA="$(gcloud run services describe bite-pos-demo \
    --region us-central1 \
    --format='value(spec.template.spec.serviceAccountName)')"

printf '%s' 'base64:PASTE_GENERATED_KEY_HERE' | gcloud secrets create bite-app-key --data-file=-
gcloud secrets add-iam-policy-binding bite-app-key \
    --member="serviceAccount:${RUNTIME_SA}" \
    --role="roles/secretmanager.secretAccessor"

# The pinned version resource is:
# projects/${PROJECT_ID}/secrets/bite-app-key/versions/1
gcloud run services update bite-pos-demo \
    --region us-central1 \
    --update-secrets=APP_KEY=projects/${PROJECT_ID}/secrets/bite-app-key:1
```

Do not let the container generate `APP_KEY` in production. A runtime-generated key can differ between Cloud Run instances, invalidating encrypted sessions and making encrypted stored data unrecoverable.

If converting an existing service from a plain `APP_KEY` environment variable, copy the exact current value into Secret Manager. Do not generate a replacement key. Deploy the secret-backed revision with 0% or low traffic first, verify it boots, then route 100% traffic to the new revision. The rollback path is the previous Cloud Run revision that still has the plain env var.

### Startup validation

`AppServiceProvider` runs production startup validation that fails fast on missing critical config. It reads via `config()` (not `env()`) and conditionally validates either `DB_SOCKET` or `DB_HOST` based on which is populated. GCS env vars are only required when `filesystems.default` is `gcs`.

## Cloud SQL Auth Proxy

Cloud Run's Cloud SQL integration mounts the Auth Proxy socket at `/cloudsql/<connection-name>/`. On the service, attach the Cloud SQL instance under **Connections → Cloud SQL Connections** so the socket is available.

## Cloud Storage

Product images, Livewire temp uploads, and Snap-to-Menu source photos all live in `bite-pos-storage`. The `ImageService` is stream-based (`Storage::get` / `Storage::put`) so it works identically on GCS and the local disk. For Livewire temp uploads, the OnboardingWizard falls back to local disk if the GCS temp-file read comes back empty — see `aa9843e`.

## First-Time Setup (from scratch)

```bash
# 1. GCP project + APIs
gcloud config set project <project>
gcloud services enable run.googleapis.com sqladmin.googleapis.com \
  storage.googleapis.com artifactregistry.googleapis.com \
  iamcredentials.googleapis.com

# 2. Cloud SQL MySQL 8.0 instance
gcloud sql instances create bite \
  --database-version=MYSQL_8_0 --tier=db-f1-micro --region=us-central1
gcloud sql databases create bite_pos --instance=bite
gcloud sql users create bite --instance=bite --password=<strong-password>

# 3. Cloud Storage bucket
gcloud storage buckets create gs://bite-pos-storage --location=us-central1

# 4. Artifact Registry repo
gcloud artifacts repositories create cloud-run-source-deploy \
  --repository-format=docker --location=us-central1

# 5. Workload Identity Federation (see google-github-actions/auth docs)
#    Create a pool + provider tied to the GitHub repo, plus a deploy
#    service account with roles/run.admin, roles/iam.serviceAccountUser,
#    roles/artifactregistry.writer, roles/cloudsql.client, roles/storage.objectAdmin.

# 6. First deploy — push to main and let the workflow do the rest.
```

## Post-Deployment Verification

### Functional
- [ ] `/health` returns 200 with all checks green
- [ ] Landing page loads over HTTPS
- [ ] Registration flow creates shop and redirects to onboarding wizard
- [ ] Snap-to-Menu uploads a photo and returns structured items
- [ ] Guest menu at `/menu/<slug>` renders with images served from `storage.googleapis.com/...`
- [ ] POS + KDS function end-to-end
- [ ] Stripe test webhook delivers and is idempotent

### Security
- [ ] `APP_DEBUG=false` in Cloud Run env
- [ ] HTTPS enforced, HTTP 301s
- [ ] Security headers present (`curl -I`)
- [ ] No `.env` file shipped in the container
- [ ] Rate limits in effect: guest order 10/15m, webhook 60/m, PIN login 5/m

### Observability
- [ ] Sentry receives a test error
- [ ] Stackdriver log channel produces structured JSON with PII masked
- [ ] Slow-request middleware logs requests >2s

## Rollback

Rollback is automatic on `/health` failure. Manual rollback:

```bash
gcloud run revisions list --service=bite-pos-demo --region=us-central1
gcloud run services update-traffic bite-pos-demo \
  --region=us-central1 --to-revisions=<revision>=100
```

## Backups

**Current state:** Cloud SQL automated backups and point-in-time recovery are **disabled** because the instance is on GCP Free Trial (which does not support them). Enable once the instance is upgraded off the free trial:

```bash
gcloud sql instances patch bite \
  --backup-start-time=02:00 \
  --retained-backups-count=7 \
  --enable-bin-log \
  --retained-transaction-log-days=7
```

Tracked in `TODOS.md` under SEC-04.

## Stripe Webhooks

Configure two endpoints in the Stripe dashboard — keep these paths stable:

- `POST https://<your-domain>/webhooks/stripe`
  Events: `checkout.session.completed`, `payment_intent.succeeded`
- `POST https://<your-domain>/webhooks/stripe/subscription`
  Events: `customer.subscription.{created,updated,deleted}`, `invoice.payment_{succeeded,failed}`

Both enforce signature verification and record into `webhook_events(provider,event_id)` for idempotency.

## Notes for the Next Migration

**Thawani Pay** is planned as the production payment processor for the Oman market; Stripe is currently the only integrated provider in code. When migrating, keep the Stripe subscription webhook for Cashier-based subscription billing and add Thawani for payment capture.

# Bite Operations Guide

Operational runbook for Bite-POS in production.

Current production target: Laravel Forge. The active pilot is `getbite.om` for
Sourdough.

For initial provisioning and deployment setup, see `DEPLOYMENT-FORGE.md`. For
the paused Cloud Run reference path, see `DEPLOYMENT.md`. For architectural
invariants, see `ARCHITECTURE.md`.

## Current Topology

- **Laravel Forge VPS** - DigitalOcean Bangalore app server managed through Forge.
- **Nginx + PHP-FPM** - installed by Forge, serving the app from `/bite/public`.
- **MySQL 8** - local MySQL on the Forge server for the pilot.
- **Local public disk** - product images and Livewire uploads live under
  `storage/app/public` and are exposed through `php artisan storage:link`.
- **Forge Scheduler** - runs `php artisan schedule:run` every minute.
- **Queue** - `QUEUE_CONNECTION=sync`; there is no queue worker daemon in the
  Forge pilot.
- **TLS** - Let's Encrypt certificate issued from Forge.
- **Email** - `info@getbite.om` is a Cloudflare Email Routing alias; app outbound
  mail can stay `MAIL_MAILER=log` until receipts or customer confirmations ship.

Cloud Run, GCS, Cloud SQL, and GitHub Actions deploy automation are paused for
the pilot. Keep their notes in `DEPLOYMENT.md` as scale-out reference material,
not as the active restaurant handoff path.

## Production Config Gate

Run the gate before handing the app to a restaurant and on every deploy:

```bash
php artisan bite:production-check
```

Forge also runs it from `deploy/forge-deploy.sh` after `config:cache` and before
migrations. The command fails fast for unsafe pilot settings:

- non-production `APP_ENV`
- `APP_DEBUG=true`
- missing or placeholder `APP_KEY`
- non-HTTPS `APP_URL`
- non-MySQL database config
- missing database host/socket or credentials
- insecure session driver, cookie, or SameSite settings
- wrong cache, queue, or filesystem drivers for the Forge pilot
- missing or wrong `public/storage` symlink for product photos
- incomplete PrintNode config when printing is enabled
- unsupported customer payment provider, or `PAYMENT_PROVIDER=stripe` without
  `STRIPE_WEBHOOK_SECRET`
- missing Stripe Billing config (`STRIPE_KEY`, `STRIPE_SECRET`,
  `STRIPE_PRO_PRICE_ID`, `STRIPE_SUBSCRIPTION_WEBHOOK_SECRET`)
- missing Sentry DSN (`SENTRY_LARAVEL_DSN`) required by production startup
  validation

For a specific restaurant, run the aggregate handoff gate:

```bash
php artisan bite:handoff-check <restaurant-slug> --minutes=60
```

It wraps the production, schema, and log gates, validates the restaurant's owner,
staff PIN, menu, billing, Pro/trial reports access, scheduler, backup-script,
and route setup, then performs live HTTP checks against `/health`, the guest
menu, each rendered product image URL, the QR SVG plus its guest-menu target
header, and PIN screen. It also performs authenticated owner/admin checks for
dashboard, POS, products, settings, reports, export, shift report, cash
reconciliation, and billing.

## Deploy Order

Deploys are manual from Forge for the pilot:

1. Forge pulls `main` using the site deploy script.
2. Composer installs optimized production dependencies.
3. Vite assets are rebuilt with `npm ci` and `npm run build`.
4. Laravel caches config.
5. `php artisan storage:link` refreshes the public image symlink.
6. `php artisan bite:production-check` validates the live env.
7. Migrations run with `php artisan migrate --force`.
8. Routes and views are cached.
9. PHP-FPM reloads last, after the new release is fully prepared.

Do not reload PHP-FPM before the production check and migrations finish.

## Health Check

`GET /health` returns 200 only when these checks pass:

- database connection works (`SELECT 1`)
- database-backed session/cache/queue tables exist when those drivers are enabled
- default storage disk is writable
- on the Forge `public` disk, `public/storage` points to `storage/app/public`
  so product photos are actually web-served
- GD extension is loaded with WebP support
- queue config is usable; `sync` passes directly, while `database` verifies the
  `jobs` table

Monitoring should hit `/health`, not `/`. A degraded response should block a
restaurant handoff until the failing field is understood.

## Scheduler

Forge must have a scheduler entry enabled manually:

```bash
php artisan schedule:run
```

Frequency: every minute.

The scheduled tasks are:

- `orders.cancel-expired` - runs every minute and cancels unpaid expired orders.
- `group-carts.clean-expired` - runs hourly and deletes expired group carts.
- `webhook-events.prune-processed` - runs daily and deletes processed webhook
  idempotency records older than 30 days.

Without the Forge scheduler, unpaid test orders will never auto-cancel and old
group carts and processed webhook records will accumulate.

## Queue

Keep this in Forge:

```dotenv
QUEUE_CONNECTION=sync
```

The pilot does not need a long-running worker. If async jobs are introduced,
switch deliberately to `database` or Redis and add a supervised worker process;
do not silently change this env var.

## Storage And Images

The Forge pilot stores menu images on local disk:

```dotenv
FILESYSTEM_DISK=public
```

Operational rules:

- `storage/app/public` is production data, not disposable build output.
- `php artisan storage:link` must be present in deploys.
- Product-image backup must include `storage/app/public`; database backups alone
  are not enough.
- `php artisan bite:handoff-check <restaurant-slug> --minutes=60` must confirm
  rendered menu image URLs return HTTP 200 `image/*` responses before handoff.
- If the droplet is replaced without restoring this directory, menu photos are
  lost even when MySQL is restored.

## Backups

Before selling the app to a restaurant, configure both backup paths:

- **Database:** Forge scheduled MySQL dump to a DO Space or S3-compatible bucket.
- **Images:** scheduled archive of `storage/app/public` to the same backup bucket.

Recommended Forge scheduled commands:

```bash
BACKUP_S3_URI=s3://<bucket>/getbite bash /home/forge/getbite.om/bite/deploy/forge-backup-database.sh
BACKUP_S3_URI=s3://<bucket>/getbite bash /home/forge/getbite.om/bite/deploy/forge-backup-storage.sh
```

After setup, perform restore drills into a throwaway database and directory
before calling the backup policy done:

```bash
RESTORE_DB_DATABASE=getbite_restore_drill bash /home/forge/getbite.om/bite/deploy/forge-restore-database-backup.sh
bash /home/forge/getbite.om/bite/deploy/forge-restore-storage-backup.sh
```

`RESTORE_DB_DATABASE` must be a pre-created throwaway database. The restore drill
refuses to import into the configured app database.

## Stripe Webhooks

Endpoints:

- `POST /webhooks/stripe` - payment events
- `POST /webhooks/stripe/subscription` - Cashier subscription lifecycle

Invariants:

- signature verification is required; invalid signatures return 400
- duplicate event IDs are absorbed idempotently through `webhook_events`
- payment mutations lock the target order row before writing
- webhook payment amounts are capped to the order balance due
- processed webhook idempotency records are pruned after 30 days; unprocessed
  records are retained for retry/debugging

Monitor signature failures, duplicate event spikes, and unexpected
`webhook_events` growth.

## Rate Limiting

- Webhooks: 60 requests per minute per IP
- Guest ordering: 10 requests per 15 minutes per IP
- Login and PIN login: 5 requests per minute per IP and identifier

Spikes in 429 responses can indicate attack traffic, a broken client, or a
script repeatedly posting orders.

## Security Monitoring

Watch for:

- PIN or manager override throttle spikes
- 403 responses on privileged routes
- suspended or lapsed shops receiving guest-order attempts
- inventory anomalies after completed order transitions
- Stripe signature verification failures
- `/livewire/*` returning static file responses

## Operational Checklists

### After Every Deploy

- [ ] `php artisan bite:production-check` passes
- [ ] `GET /health` returns 200
- [ ] `php artisan bite:schema-check` passes after migrations
- [ ] `php artisan bite:log-check --minutes=60` passes
- [ ] `php artisan bite:handoff-check sourdough --minutes=60` passes
- [ ] `php artisan schedule:list --json` shows `orders.cancel-expired`,
      `group-carts.clean-expired`, and `webhook-events.prune-processed`
- [ ] Guest menu loads at `https://getbite.om/menu/sourdough`
- [ ] Run `docs/SOURDOUGH-LIVE-SMOKE.md` for the pilot counter, guest QR,
      mobile browser, Arabic / RTL, and image fallback pass
- [ ] Place a test order and confirm it appears on POS and KDS
- [ ] Mark a test order ready and confirm the tracker updates
- [ ] Leave an unpaid test order to expire and confirm it auto-cancels
- [ ] Confirm recent logs have no new application errors with
      `php artisan bite:log-check --minutes=60`

### Before Restaurant Handoff

- [ ] DNS for `getbite.om` and `www.getbite.om` resolves to the Forge server
- [ ] TLS certificate is active and HTTPS is the canonical URL
- [ ] `php artisan bite:production-check` passes on the Forge server
- [ ] `php artisan bite:handoff-check <restaurant-slug> --minutes=60` passes on
      the Forge server
- [ ] Reports/export, shift report, and cash reconciliation are reachable by
      the owner or manager account
- [ ] Only the explicit `SourdoughMenuSeeder` has been run; default demo seeding
      is not used in production
- [ ] Scheduler is enabled in Forge and the expiry test passes
- [ ] Database backup is scheduled and one restore drill has succeeded
- [ ] Image backup for `storage/app/public` is scheduled and one restore drill
      has succeeded
- [ ] Admin owner credentials are stored in a password manager and referenced
      from `docs/RESTAURANT-HANDOFF-RECORD.md`
- [ ] Staff PIN login works on a real tablet-sized viewport
- [ ] Public QR menu and tracker work from a mobile network

### Weekly

- [ ] Review application logs and top exceptions
- [ ] Check `failed_jobs`, `jobs`, and `webhook_events` table sizes
- [ ] Confirm available disk space on the Forge server
- [ ] Confirm the latest database and image backups completed
- [ ] Place a small cash test order through POS and KDS

## Useful Commands

```bash
# Production readiness
php artisan bite:production-check

# Health endpoint from the box
curl -fsS https://getbite.om/health

# Schedule visibility
php artisan schedule:list --json

# Post-migration schema readiness
php artisan bite:schema-check

# Recent application errors
php artisan bite:log-check --minutes=60

# Include copied/mixed Laravel environment channels only when auditing a shared log file.
php artisan bite:log-check --minutes=60 --include-all-environments

# Restaurant-specific handoff readiness
php artisan bite:handoff-check sourdough --minutes=60

# Backup restore drills
RESTORE_DB_DATABASE=getbite_restore_drill bash /home/forge/getbite.om/bite/deploy/forge-restore-database-backup.sh
bash /home/forge/getbite.om/bite/deploy/forge-restore-storage-backup.sh

# Migration state
php artisan migrate:status

# Local verification
php artisan test --compact
./vendor/bin/pint --test
composer audit --locked
npm audit --audit-level=high
npm run build
```

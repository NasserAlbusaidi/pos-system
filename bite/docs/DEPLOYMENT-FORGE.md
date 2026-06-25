# Bite-POS — Forge Pilot Deployment (`getbite.om`)

**This is the current production target for the Sourdough pilot.** Cloud Run /
GCS (see `DEPLOYMENT.md`) is paused. The pilot runs on a single **Laravel Forge**
VPS with images on the **local `public` disk** (no GCS), pay-at-counter, and a
shop-level QR menu at `https://getbite.om/menu/{slug}`.

Why Forge over Cloud Run: Forge persists `.env` and local disk across restarts
(Cloud Run is ephemeral), which makes local-disk images and a fixed `APP_KEY`
trivial. Trade-off: you own the box — scheduler, queue, backups, and TLS are
your responsibility, not the platform's. The Forge-specific gotchas below exist
because the Cloud Run process model (`docker/supervisord.conf`) does **not** apply
on Forge.

---

## Part 1 — Domain, DNS & Email

### 1.1 Register `getbite.om` and delegate DNS to Cloudflare

1. Register `getbite.om` through your `.om` registrar (have an Omani ID/CR ready —
   `.om` registrants usually need local presence).
2. **Critical check:** confirm the registrar lets you set **custom nameservers**.
   - If **yes** → add the site to Cloudflare, copy the two `*.ns.cloudflare.net`
     nameservers it gives you, and set them at the registrar. DNS + free Email
     Routing both live on Cloudflare.
   - If **no** (registrar locks DNS to its own panel) → manage DNS at the
     registrar and use **Zoho Mail (free tier)** for `info@getbite.om` instead of
     Cloudflare Email Routing. The rest of this guide is unchanged.

### 1.2 Point the domain at the Forge box (DNS-only / grey cloud)

Add these once the droplet exists (Part 2 gives you the IP):

| Type | Name | Value | Proxy |
|------|------|-------|-------|
| A | `getbite.om` (`@`) | `<DROPLET_IP>` | DNS only (grey) |
| A | `www` | `<DROPLET_IP>` | DNS only (grey) |

Keep it grey for the pilot. Orange (proxied) edge-caches the webp images but then
Cloudflare's IPs sit in front — you'd have to set `TRUSTED_PROXIES` to Cloudflare's
CIDRs or rate-limit + loyalty key off the wrong IP. Defer that.

### 1.3 `info@getbite.om` — Cloudflare Email Routing (free, receive + reply)

In Cloudflare → **Email → Email Routing → Enable**:

1. Cloudflare **auto-creates** 3 MX records (`route1/2/3.mx.cloudflare.net`) and the
   root SPF TXT (`v=spf1 include:_spf.mx.cloudflare.net ~all`). Don't hand-create
   these — the wizard does it. They are shown here only so you recognise them:

   | Type | Name | Value |
   |------|------|-------|
   | MX | `getbite.om` | `route1.mx.cloudflare.net` (+ route2, route3) |
   | TXT | `getbite.om` | `v=spf1 include:_spf.mx.cloudflare.net ~all` |

2. **Destination address:** add your personal Gmail as a destination and click the
   verification link Cloudflare emails you.
3. **Custom address:** `info@getbite.om` → forward to that Gmail. (Optionally add a
   catch-all → same Gmail.)
4. To **reply as** `info@getbite.om`: Gmail → Settings → Accounts → "Send mail as" →
   add `info@getbite.om`. Gmail relays via its own SMTP; no extra infra.

This is forwarding, not a stored mailbox — fine for a pilot contact address. Want a
real IMAP mailbox later → Zoho free or Google Workspace ($6/user/mo).

### 1.4 DMARC (recommended, one record)

| Type | Name | Value |
|------|------|-------|
| TXT | `_dmarc.getbite.om` | `v=DMARC1; p=none; rua=mailto:info@getbite.om` |

`p=none` just monitors; tighten to `quarantine`/`reject` only after you've added
sending (below) and confirmed alignment.

### 1.5 Sending app email — only when you need it (pre-stage)

The pilot sends **almost no email**: new orders go via `database` + WhatsApp,
`WelcomeTo` is unused, and the only live outbound path is staff forgot-password.
So leave `MAIL_MAILER=log` and skip a sending provider for now.

When you do add real flows (emailed receipts, customer confirmations), use
**Resend** on a **`send.getbite.om` subdomain** — that keeps Resend's SPF/DKIM off
the root, so it never clashes with the Email-Routing SPF (you can only have one
`v=spf1` record per name). Resend's onboarding generates the exact records; you'll
add a DKIM CNAME (e.g. `resend._domainkey`), a subdomain SPF, and a bounce MX.
Then set `MAIL_MAILER=resend`, `RESEND_KEY=…`, `MAIL_FROM_ADDRESS=noreply@getbite.om`.

---

## Part 2 — Server (DigitalOcean Bangalore via Forge)

1. Forge → **Connect** your DigitalOcean account.
2. **Create Server** → DigitalOcean → region **Bangalore (BLR1)** → size **2GB /
   1 vCPU** (the 1GB box is too tight for MySQL + Redis + PHP-FPM). Forge's "App
   Server" recipe installs Nginx, PHP 8.x, MySQL 8, Redis, Node.
3. Note the **droplet IP** → fill it into the A records in §1.2.

---

## Part 3 — Site & Deploy

1. Forge → server → **New Site** → domain `getbite.om`, web directory `/bite/public`.
2. **Git repository:** `NasserAlbusaidi/pos-system`, branch `main`.
3. **Environment:** paste `.env.production.example` (repo root), fill the
   `<PLACEHOLDERS>`. Generate the key once: `php artisan key:generate --show` and
   paste it as `APP_KEY`.
4. **Deploy script:** replace Forge's default with `bite/deploy/forge-deploy.sh`
   from the repo. The script `cd`s into `/home/forge/getbite.om/bite` before
   Composer, npm, Artisan, and migrations.
5. **SSL:** Forge → site → SSL → **Let's Encrypt** (HTTP-01 works on `.om` with
   grey-cloud DNS; the box must be reachable on 80/443).
6. **First deploy:** click Deploy.

Before handing the app to a restaurant, run the production config gate on the
Forge box:

```bash
php artisan bite:production-check
```

It fails fast when the pilot env is unsafe for a real venue: debug on, non-HTTPS
URL, missing database host/socket or credentials, insecure sessions, wrong
queue/cache/storage driver, missing Stripe Billing keys/price/webhook secret,
enabled PrintNode printing without API key/printer/HTTPS endpoint, unsupported
customer payment provider, `PAYMENT_PROVIDER=stripe` without
`STRIPE_WEBHOOK_SECRET`, missing Sentry DSN, or non-production `APP_ENV`.

### One-time, after the first deploy (not in the deploy script)
```bash
php artisan config:clear
php artisan db:seed --class=SourdoughMenuSeeder --force   # 33 bilingual items
```

Do not run the default `php artisan db:seed` command on Forge. The default
`DatabaseSeeder` is local-demo-only and refuses to run in production because it
creates known demo credentials.

Before running the seed in production, set `SOURDOUGH_ADMIN_PASSWORD` in Forge to
the owner handoff password for `admin@sourdough.om`. The seeder refuses to create
that admin in production when the password is missing, still `password`, or too
short.

---

## Part 4 — Forge-specific gotchas (READ THIS)

- **Scheduler — must enable manually.** The scheduler lives in
  `docker/supervisord.conf` (`[program:scheduler]`), which is **inside the Cloud
  Run image only**. Forge never reads it. Forge → site → **Scheduler → enable**
  (`php artisan schedule:run`, every minute). Without it `Order::cancelExpired()`
  (every minute) and `GroupCart::cleanExpired()` (hourly) never run, so unpaid
  orders never auto-cancel.
- **Queue = `sync`.** The Dockerfile pins `QUEUE_CONNECTION=sync` and the app
  dispatches no async jobs. Keep `sync` in the Forge env — no queue worker daemon
  needed.
- **`storage:link` + image backups.** Images are on the local `public` disk, served
  via the symlink. The deploy script runs `storage:link`. Because the images are
  **not in git**, a droplet loss loses every product photo — see Part 5 backups.
- **`TRUSTED_PROXIES` stays unset.** Forge's nginx forwards from loopback, which the
  default in `bootstrap/app.php` already trusts. Only set it if you flip Cloudflare
  to orange.

---

## Part 5 — Backups (#20) & Verification

### Backups
- **Database:** Forge → server → **Backups** → scheduled MySQL dump to a DO Space /
  S3 bucket.
- **Images:** cron a tar of `storage/app/public` to the same bucket (not covered by
  the DB backup, not in git):
  ```bash
  BACKUP_S3_URI=s3://<bucket>/getbite bash /home/forge/getbite.om/bite/deploy/forge-backup-database.sh
  BACKUP_S3_URI=s3://<bucket>/getbite bash /home/forge/getbite.om/bite/deploy/forge-backup-storage.sh
  ```
- **Restore drill:** before handoff, import the latest DB backup into a
  pre-created throwaway database and extract the latest image archive into a
  separate restore directory:
  ```bash
  RESTORE_DB_DATABASE=getbite_restore_drill bash /home/forge/getbite.om/bite/deploy/forge-restore-database-backup.sh
  bash /home/forge/getbite.om/bite/deploy/forge-restore-storage-backup.sh
  ```
  The DB restore script refuses to import into the configured app database.

### Post-deploy verification
- [ ] `php artisan bite:production-check` passes
- [ ] `https://getbite.om/menu/sourdough` loads over HTTPS, photos render
- [ ] `php artisan bite:schema-check` passes after migrations
- [ ] `php artisan bite:log-check --minutes=60` has no recent application errors
      for the current Laravel environment; use `--include-all-environments`
      only when intentionally auditing a mixed log file
- [ ] `php artisan bite:handoff-check sourdough --minutes=60` passes, including
      Pro/trial reports access plus live `/health`, guest menu, rendered product
      image URLs, QR SVG target, PIN screen HTTP checks, and authenticated
      owner/admin dashboard, POS, products, settings, reports, export, shift
      report, cash reconciliation, and billing checks
- [ ] Place a test order → it appears on the POS dashboard / KDS
- [ ] Wait >1 min → an unpaid test order auto-cancels (proves the scheduler runs)
- [ ] Send a test email to `info@getbite.om` → lands in your Gmail
- [ ] Fill `docs/RESTAURANT-HANDOFF-RECORD.md` with password-manager item
      references and verification evidence; do not commit plaintext secrets.

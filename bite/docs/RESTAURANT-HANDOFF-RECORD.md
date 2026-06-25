# Restaurant Handoff Record

Use this template for each paid restaurant handoff. Completed records must not
commit plaintext passwords, staff PINs, Stripe secrets, database credentials, or
backup bucket credentials. Store secrets in a password manager and write only the
password-manager item names here.

Recommended local path for filled records: `docs/handoffs/<restaurant-slug>.md`.
That path is ignored by git.

## Restaurant

- Restaurant name:
- Production domain:
- Shop slug:
- Handoff date:
- Bite operator:
- Restaurant owner:

## Credential References

- Owner admin email:
- Owner password-manager item:
- Staff PIN password-manager item:
- Stripe password-manager item:
- Forge/SSH password-manager item:
- Backup bucket password-manager item:

## Production Gate

- `php artisan bite:production-check` result:
- `GET /health` result:
- `php artisan bite:schema-check` result:
- `php artisan bite:log-check --minutes=60` result for the current Laravel
  environment:
- `php artisan bite:handoff-check <restaurant-slug> --minutes=60` result,
  including reports access, owner/admin pages, live health/menu/product image
  URL/QR SVG target/PIN HTTP checks:
- `php artisan migrate:status` checked:
- `php artisan schedule:list --json` checked:
- Latest deploy commit:

## Restaurant Flow Proof

- Guest menu URL:
- Guest QR URL:
- Guest QR SVG `Content-Type` and `X-Bite-QR-Target` checked:
- Guest menu product image URLs checked as HTTP 200 `image/*` responses:
- Owner/admin dashboard, POS, products, settings, reports, export, shift report,
  cash reconciliation, and billing checked:
- Orderable product photo coverage:
- Tracker URL from test order:
- POS tablet PIN login tested:
- Kitchen display tested:
- Cash test order ID:
- Card/Stripe test order ID, if enabled:
- Refund/void scenario tested:
- Shift close tested:
- Reports/export checked:

## Backup And Restore Proof

- Database backup schedule:
- Database backup script result:
- `deploy/forge-restore-database-backup.sh` throwaway restore result:
- Image backup schedule:
- Image backup script result:
- `deploy/forge-restore-storage-backup.sh` restore result:
- Backup bucket/path:

## Handoff Acceptance

- Owner received admin URL:
- Owner verified login:
- Staff received PIN instructions:
- QR code printed or shared:
- Known limitations explained:
- Support contact shared:
- Owner acceptance:
- Notes:

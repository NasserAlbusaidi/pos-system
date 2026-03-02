# Bite Operations Guide

## Environment Variables
Required for baseline app:
- `APP_KEY`
- DB settings (`DB_CONNECTION`, etc.)

Payments:
- `PAYMENT_PROVIDER=stripe`
- `STRIPE_WEBHOOK_SECRET`

Printing (optional):
- `PRINTNODE_ENABLED`
- `PRINTNODE_API_KEY`
- `PRINTNODE_PRINTER_ID`

## Deploy Order
1. Deploy database migrations first.
2. Deploy application code.
3. Restart queue workers.
4. Confirm scheduler and webhook health.

## Migrations of Interest
- `orders.tracking_token`
- `orders.fulfilled_at`
- `webhook_events` table

## Runtime Processes
- Web server (PHP-FPM/Octane/etc.)
- Queue workers
- Scheduler (`php artisan schedule:run` every minute)

## Health and Verification Checklist
- `php artisan route:list -v`
- `php artisan test`
- `./vendor/bin/pint --test`
- `npm run build`

## Stripe Webhook Operations
Endpoint:
- `POST /webhooks/stripe`

Expected behavior:
- Invalid signatures return `400`.
- Duplicate event IDs are accepted idempotently with no duplicate payment mutation.

Monitoring signals:
- Signature verification failures
- Duplicate webhook rate spikes
- Payment/order state mismatches

## Security Monitoring
Track and alert on:
- PIN/manager override throttling spikes
- 403 RBAC denials on privileged routes
- Inventory anomalies after completion transitions

## Rollback Notes
If deployment rollback is needed:
- Keep webhook path unchanged
- Preserve webhook idempotency table data
- Do not re-enable unsigned webhook processing

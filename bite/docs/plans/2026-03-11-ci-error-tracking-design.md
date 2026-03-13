# CI + Error Tracking Design

**Date:** 2026-03-11
**Status:** Approved

## CI — GitHub Actions

Single workflow `ci.yml` triggers on every push and pull request.

**Environment:**
- PHP 8.2
- SQLite in-memory (matches `phpunit.xml`)

**Steps:**
1. Checkout code
2. Install PHP dependencies (`composer install`)
3. Run tests (`php artisan test`)
4. Run lint check (`./vendor/bin/pint --test`)

## Error Tracking — Sentry

- Package: `sentry/sentry-laravel`
- Config: `config/sentry.php` (published via artisan)
- DSN: loaded from `SENTRY_LARAVEL_DSN` env var — never hardcoded
- Captures: unhandled exceptions, queue failures, log errors

## Out of Scope

- Auto-deploy (will add when hosting platform is chosen)
- Preview environments for PRs
- Performance monitoring
- Slack/Discord notifications

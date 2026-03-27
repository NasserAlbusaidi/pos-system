---
phase: 06-containerization-cloud-services
plan: 01
subsystem: container-infrastructure
tags: [docker, nginx, php-fpm, supervisord, cloud-sql, mysql, production]
completed: "2026-03-27T14:31:31Z"
duration: "~10 minutes"
tasks_completed: 3
files_changed: 7

dependency_graph:
  requires: []
  provides: [production-container-architecture, cloud-sql-mysql-connectivity, secrets-free-image]
  affects: [Dockerfile, docker/start.sh, docker/nginx.conf, docker/php-fpm.conf, docker/supervisord.conf, config/database.php, .env.example]

tech_stack:
  added: [nginx, supervisor, pdo_mysql, gd-with-webp]
  patterns: [nginx-php-fpm-unix-socket, supervisord-single-container, cloud-sql-auth-proxy-socket]

key_files:
  created:
    - docker/nginx.conf
    - docker/php-fpm.conf
    - docker/supervisord.conf
  modified:
    - Dockerfile
    - docker/start.sh
    - .dockerignore
    - config/database.php
    - .env.example

decisions:
  - "Nginx + PHP-FPM via supervisord in single container (Cloud Run requires single process group)"
  - "clear_env=no in PHP-FPM pool so Cloud Run env vars are visible to PHP scripts"
  - "MySQL default in config/database.php; tests unaffected by phpunit.xml SQLite override"
  - "No secrets baked in — all credentials injected at runtime via Cloud Run environment variables"
  - "GD with WebP/JPEG/PNG/FreeType support for intervention/image v3 compatibility"

metrics:
  duration: "~10 minutes"
  completed: "2026-03-27"
  tasks: 3
  files: 7
---

# Phase 6 Plan 1: Production Container Architecture Summary

Production-grade Nginx + PHP-FPM container replacing single-threaded `php artisan serve` with supervisord-managed process pair, Cloud SQL MySQL 8.0 connectivity via Unix socket, and zero secrets baked into the Docker image.

## What Was Built

### Task 1: Nginx, PHP-FPM, Supervisord Configs (commit: 20787bf)

Three new config files for the production container:

- **docker/nginx.conf** — Listens on port 8080 (Cloud Run default), proxies PHP requests to PHP-FPM via Unix socket at `/run/php-fpm.sock`, serves static assets with 30-day cache headers (`immutable`), enforces `client_max_body_size 20M` at the Nginx layer (replaces the old PHP CLI `-d upload_max_filesize=20M` flags), gzip enabled for CSS/JS/JSON/SVG, `daemon off` for supervisord compatibility.

- **docker/php-fpm.conf** — Dynamic pool (`pm.max_children=10`, `pm.start_servers=2`), Unix socket with `listen.owner/group=www-data`, PHP ini overrides for upload limits (20M files, 50M post, 10 files, 256M memory), critically `clear_env=no` so Cloud Run's injected environment variables are visible to all PHP-FPM worker processes.

- **docker/supervisord.conf** — `nodaemon=true` for foreground operation, manages `[program:nginx]` and `[program:php-fpm]` with stdout/stderr forwarded to `/dev/stdout` and `/dev/stderr` (captured by Cloud Run logging), `autorestart=true` on both programs.

### Task 2: Dockerfile and start.sh Rewrite (commit: 6e35ded)

**Dockerfile changes:**
- Base image: `php:8.4-fpm-bookworm` (was `php:8.4-cli-bookworm`)
- System packages: removed `git`, `libsqlite3-dev`; added `nginx`, `supervisor`, `libfreetype6-dev`, `libjpeg62-turbo-dev`, `libpng-dev`, `libwebp-dev`
- PHP extensions: removed `pdo_sqlite`; added `pdo_mysql` and `gd` (configured with `--with-freetype --with-jpeg --with-webp`)
- Copies all three docker config files to their system paths
- `DB_CONNECTION=mysql` (was `DB_CONNECTION=sqlite`)
- Removed `DB_DATABASE=/var/www/html/database/database.sqlite` env var
- Removed build-time `migrate --force --seed` (seeding is D-04: first deploy only, not image build)
- Added `chown -R www-data:www-data storage bootstrap/cache` for FPM write permissions
- `SENTRY_ENVIRONMENT=production` (was `staging`)

**docker/start.sh changes:**
- Removed `touch database/database.sqlite` and `mkdir -p database`
- Added `php artisan config:cache`, `route:cache`, `view:cache` for production performance
- Added `mkdir -p /run` for PHP-FPM socket directory
- Entrypoint: `exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf` (was `exec php artisan serve`)

**.dockerignore additions:**
`.planning`, `tests`, `docker-compose*.yml`, `CLAUDE.md`, `journal.md`, `claude-journal.md`, `TODOS.md`, `DEVELOPMENT.md`, `README.md` — development artifacts excluded from production image.

### Task 3: MySQL Default and Cloud SQL Documentation (commit: aa3e3a9)

**config/database.php:**
- Changed `'default' => env('DB_CONNECTION', 'sqlite')` to `'default' => env('DB_CONNECTION', 'mysql')`
- The `mysql` connection already had `'unix_socket' => env('DB_SOCKET', '')` — no structural changes needed for Cloud SQL Auth Proxy support

**phpunit.xml:** Already sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` — tests unaffected by the default change.

**.env.example:**
- Updated DB section from commented-out MySQL to active `DB_CONNECTION=mysql` with `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Added Cloud SQL Auth Proxy socket comment: `# DB_SOCKET=/cloudsql/PROJECT_ID:REGION:INSTANCE_NAME`
- Added production secrets inventory comment listing all secrets needed for Cloud Run: `APP_KEY`, `DB_PASSWORD`, `STRIPE_SECRET`, `STRIPE_KEY`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_SUBSCRIPTION_WEBHOOK_SECRET`, `SENTRY_LARAVEL_DSN`, `GEMINI_API_KEY`

## Verification

Post-completion verification (all passed):
1. `grep -q "php:8.4-fpm-bookworm" Dockerfile` — FPM base image confirmed
2. `grep -q "pdo_mysql" Dockerfile && ! grep -q "pdo_sqlite" Dockerfile` — MySQL, no SQLite
3. `grep -q "fastcgi_pass" docker/nginx.conf` — Nginx proxies to FPM
4. `grep -q "supervisord" docker/start.sh` — Process manager entrypoint
5. `grep -q "DB_CONNECTION=mysql" .env.example` — MySQL as default
6. `! grep -q "artisan serve" docker/start.sh` — No more PHP CLI server
7. `grep -q ".env" .dockerignore` — Secrets excluded from image
8. `php artisan test` — 201 tests passed, 473 assertions (SQLite in-memory via phpunit.xml)

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None — all configuration is complete and functional.

## Self-Check: PASSED

Files verified:
- `docker/nginx.conf` — exists, contains all required directives
- `docker/php-fpm.conf` — exists, contains `clear_env = no`
- `docker/supervisord.conf` — exists, contains both program sections
- `Dockerfile` — verified via acceptance criteria checks
- `docker/start.sh` — verified via acceptance criteria checks
- `.dockerignore` — verified `.planning` and `.env` present
- `config/database.php` — verified `'default' => env('DB_CONNECTION', 'mysql')`
- `.env.example` — verified `DB_CONNECTION=mysql` and Cloud SQL socket comment

Commits verified:
- `20787bf` — feat(06-01): add Nginx, PHP-FPM pool, and supervisord configs
- `6e35ded` — feat(06-01): rewrite Dockerfile and start.sh for Nginx + PHP-FPM production setup
- `aa3e3a9` — feat(06-01): configure MySQL as default DB connection and document Cloud SQL Auth Proxy

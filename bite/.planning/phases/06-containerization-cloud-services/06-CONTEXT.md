# Phase 6: Containerization & Cloud Services - Context

**Gathered:** 2026-03-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Migrate Bite-POS from a demo-grade container (PHP CLI + SQLite) to a production-grade containerized service with Nginx + PHP-FPM, Cloud SQL MySQL 8.0, Google Cloud Storage for file uploads, and environment-based secrets management. No hardcoded secrets in the container image.

</domain>

<decisions>
## Implementation Decisions

### Container Architecture
- **D-01:** Upgrade from `php artisan serve` to **Nginx + PHP-FPM** in the Docker container. Nginx serves static assets (CSS/JS/fonts/images) and proxies PHP requests to FPM. This resolves the current single-threaded limitation and the upload size issue (previously fixed with PHP CLI `-d` flags — now handled properly via Nginx `client_max_body_size` and PHP-FPM `upload_max_filesize`).
- **D-02:** Multi-stage Dockerfile retained: Node stage for Vite build, PHP stage for app. Add Nginx to the PHP stage or use a supervisor to run both processes in one container (Cloud Run requires a single container).

### Database Strategy
- **D-03:** Connect to **Cloud SQL MySQL 8.0 via Cloud SQL Auth Proxy** using Cloud Run's built-in connection support (add `--add-cloudsql-instances` flag). Connection via Unix socket, no public IP needed.
- **D-04:** **Seed on first deploy only.** Run migrations + DatabaseSeeder once when setting up Cloud SQL. After that, every deploy runs `php artisan migrate --force` only — no seeding. Demo shop (Sourdough) persists in the database.
- **D-05:** Remove SQLite from the production Dockerfile. SQLite remains for tests only (phpunit.xml).

### File Storage Migration
- **D-06:** Switch `FILESYSTEM_DISK` to `gcs` in production. Use the `google/cloud-storage` Laravel filesystem driver. Product images and all uploads go to a GCS bucket.
- **D-07:** **GCS public URLs** for serving images. Bucket is publicly readable — images served directly from `storage.googleapis.com` URLs. Product photos are not sensitive data. No signed URLs or proxying needed.
- **D-08:** **Livewire temporary uploads also use GCS.** Configure Livewire's temp upload disk to use GCS so that multi-instance Cloud Run deployments can access temp files across instances. This fully resolves the Snap-to-Menu upload failure in production.
- **D-09:** ImageService must be updated to work with GCS disk instead of `public` disk. The `processUpload()` method currently uses `Storage::disk('public')->path()` for absolute paths — GCS doesn't have local absolute paths. Needs refactoring to use stream-based operations.

### Secrets Management
- **D-10:** Use **Cloud Run environment variables** for all secrets. Cloud Run's built-in Secret Manager integration allows referencing secrets as env vars without SDK code. Secrets include: `APP_KEY`, `DB_PASSWORD`, `STRIPE_SECRET`, `SENTRY_DSN`, `OPENAI_API_KEY`, `GCS_BUCKET`.
- **D-11:** `.env` file must NOT be baked into the container image. The `.dockerignore` already excludes `.env`. Verify this is enforced and add a build-time check if needed.

### Claude's Discretion
- Supervisor vs. multi-process approach for running Nginx + PHP-FPM in a single container (e.g., supervisord, s6-overlay, or a simple shell script)
- Nginx configuration details (worker processes, buffer sizes, timeout values)
- GCS bucket naming convention and region selection
- Migration script approach for transitioning from SQLite to Cloud SQL

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Container & Deployment
- `Dockerfile` — Current working Dockerfile (PHP 8.4 CLI, multi-stage build with Node)
- `docker/start.sh` — Current startup script (key generation, migrations, artisan serve)
- `.dockerignore` — Files excluded from Docker context
- `.github/workflows/ci.yml` — Current CI workflow (tests + lint only, no deploy)

### File Storage
- `config/filesystems.php` — Filesystem disk configuration (currently local + s3 template)
- `app/Services/ImageService.php` — Image processing pipeline (uses `Storage::disk('public')->path()` — needs GCS refactoring)
- `app/Livewire/OnboardingWizard.php` — Snap-to-Menu file uploads (uses Livewire `WithFileUploads`)
- `app/Livewire/ProductManager.php` — Product image uploads

### Database
- `.env.example` — Environment variable template (currently SQLite default)
- `config/database.php` — Database connection configuration

### Requirements
- `.planning/REQUIREMENTS.md` — DEPLOY-01, DEPLOY-02, DEPLOY-03, SEC-02 mapped to this phase

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `ImageService` — Processes uploads into 3 size variants (thumb/card/full). Needs refactoring for GCS but core logic is reusable.
- `Dockerfile` multi-stage build — Node + PHP stages already working. Extend rather than rewrite.
- `docker/start.sh` — Startup script pattern (key gen, migrations). Adapt for Nginx + FPM.

### Established Patterns
- `Storage::disk()` abstraction — Laravel's filesystem abstraction means switching to GCS is mostly config-level. Exception: `ImageService::processUpload()` uses `->path()` for absolute paths (GCS incompatible).
- Environment-based config — App already reads all config from env vars via `config/*.php`. No hardcoded values.
- Livewire `WithFileUploads` trait — Standard pattern, supports configurable temp disk.

### Integration Points
- `config/filesystems.php` — Add GCS disk configuration
- `config/livewire.php` — Publish and configure temp upload disk
- `Dockerfile` — Replace PHP CLI with Nginx + PHP-FPM
- `docker/start.sh` — Start Nginx + FPM instead of artisan serve
- Cloud Run service config — Add Cloud SQL connection, env vars, secrets

</code_context>

<specifics>
## Specific Ideas

- The current `bite-pos-demo` Cloud Run service serves as a reference for what's already deployed and working
- The Snap-to-Menu upload fix (PHP upload limits in start.sh) should be properly handled by Nginx + PHP-FPM config rather than CLI flags
- Sourdough Oman is the first production client — the container must handle their 33-item bilingual menu with photos reliably

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 06-containerization-cloud-services*
*Context gathered: 2026-03-27*

# Phase 8: CI/CD & Data Safety - Research

**Researched:** 2026-03-27
**Domain:** GitHub Actions CI/CD pipeline for GCP Cloud Run + Cloud SQL automated backups
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Auto-deploy on push to main. Tests pass -> build image -> push to Artifact Registry -> deploy to Cloud Run. No manual approval gate.
- **D-02:** PRs run tests + lint + Docker build (no push/deploy). Catches Dockerfile and asset build failures before merge. Only pushes to main trigger the full deploy pipeline.
- **D-03:** Remove path filter (`bite/**`). Any push to main triggers the pipeline. The `bite/` subdirectory structure was a leftover — the repo root IS the app.
- **D-04:** Remove `working-directory: bite`. Same reason as D-03 — no subdirectory nesting.
- **D-05:** Pipeline hits GET /health after deploy. Wait 30 seconds after `gcloud run deploy`, then check the health endpoint.
- **D-06:** Auto-rollback on health check failure. If /health returns non-200, the pipeline runs `gcloud run services update-traffic` to shift 100% back to the previous revision.
- **D-07:** GitHub Actions email for failure notifications. No additional notification channels — solo founder, built-in email is sufficient.
- **D-08:** Migrations run at container startup (existing `docker/start.sh` behavior). Not in the pipeline.
- **D-09:** 7-day backup retention. Cloud SQL automated daily backups retained for 7 days.
- **D-10:** Point-in-time recovery (PITR) enabled. Binary logging allows restore to any specific second within the retention window.
- **D-11:** Backup config documented in plan only. No separate runbook file.
- **D-12:** Tag images with commit SHA + `latest`. Each image tagged as `bite-pos:<commit-sha>` AND `bite-pos:latest`.
- **D-13:** Keep all images indefinitely. No automatic cleanup policy.
- **D-14:** Use existing `cloud-run-source-deploy` Artifact Registry repo. Already exists in `us-central1`.
- **D-15:** GCP credentials stored as GitHub secrets. `GCP_PROJECT_ID`, `CLOUD_RUN_SERVICE`, `GCP_REGION` configured as GitHub Actions secrets.

### Claude's Discretion
- GitHub Actions authentication method for GCP (Workload Identity Federation vs service account key)
- Docker build caching strategy in CI
- Exact health check retry logic (how many retries, backoff)
- Cloud SQL backup window timing (time of day for daily backup)
- Artifact Registry image path format

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DEPLOY-04 | GitHub Actions workflow runs tests, builds Docker image, pushes to Artifact Registry, and deploys to Cloud Run on push to main | GitHub Actions WIF auth → Docker build → Artifact Registry push → `deploy-cloudrun` action |
| SEC-04 | Cloud SQL automated backups enabled with retention policy and point-in-time recovery | `gcloud sql instances patch` with `--backup-start-time`, `--retained-backups-count=7`, `--enable-bin-log`, `--retained-transaction-log-days=7` — requires sqladmin API enablement first |
</phase_requirements>

---

## Summary

Phase 8 involves two orthogonal tasks: (1) extending the existing `ci.yml` GitHub Actions workflow into a full test-build-deploy pipeline for Google Cloud Run, and (2) enabling Cloud SQL automated backups with point-in-time recovery. The existing workflow already handles PHP testing and Pint linting — it needs Docker build + Artifact Registry push + Cloud Run deploy + post-deploy health check + rollback steps added.

For GCP authentication, Workload Identity Federation (WIF) is the recommended approach over service account keys. WIF generates short-lived credentials that expire in one hour, eliminates the risk of long-lived key leakage, and is well-supported by the `google-github-actions/auth@v3` action. The `iamcredentials.googleapis.com` API is already enabled on the GCP project, which is a prerequisite for WIF.

For Cloud SQL backups, the `sqladmin.googleapis.com` API is NOT currently enabled on the project — this must be enabled before any `gcloud sql instances patch` commands can run. Once enabled, a single `gcloud sql instances patch` command with `--backup-start-time`, `--retained-backups-count=7`, `--enable-bin-log`, and `--retained-transaction-log-days=7` will satisfy both D-09, D-10, and SEC-04.

**Primary recommendation:** Use Workload Identity Federation with `google-github-actions/auth@v3` and `google-github-actions/deploy-cloudrun@v3`. For Docker build caching use GHA cache type with `docker/build-push-action@v6`. Enable Cloud SQL backups via `gcloud sql instances patch` after first enabling the sqladmin API.

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `google-github-actions/auth` | v3 | GCP auth via WIF or SA key in GitHub Actions | Official Google action, maintained by Google |
| `google-github-actions/setup-gcloud` | v2 | Install gcloud CLI in GitHub Actions runner | Official Google action |
| `google-github-actions/deploy-cloudrun` | v3 | Deploy image to Cloud Run from GitHub Actions | Official Google action, wraps `gcloud run deploy` |
| `docker/setup-buildx-action` | v3 | Enable Docker BuildKit with buildx | Required for GHA cache type with multi-stage builds |
| `docker/build-push-action` | v6 | Build Docker image and push to registry | Standard action for multi-platform image builds |
| `actions/checkout` | v4 | Checkout repository code | Required first step for all workflows |
| `actions/cache` | v4 | Cache Composer dependencies | Already in use in existing ci.yml |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `shivammathur/setup-php` | v2 | Set up PHP with extensions | Already in use — keep for test job |
| Docker GHA cache backend | built-in | Cache Docker build layers across CI runs | Main job — reduces build time for unchanged layers |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| WIF (Workload Identity Federation) | Service Account JSON key stored in GitHub Secret | SA key works but is a long-lived credential that requires rotation and can be leaked. WIF is keyless and safer. |
| `deploy-cloudrun` action | Raw `gcloud run deploy` CLI | Action wraps gcloud cleanly; raw gcloud also works if flags need customization. For this phase, action is cleaner. |
| GHA cache for Docker layers | Artifact Registry cache backend | Registry cache supports intermediate layer caching in multi-stage builds (mode=max). GHA cache is simpler but has 10 GB limit per repo. |

**Installation:** No npm/composer installs needed — these are GitHub Actions actions referenced by version tag in YAML.

---

## Architecture Patterns

### Recommended Workflow Structure

The single `ci.yml` file contains two jobs:

```
.github/workflows/ci.yml
├── job: test           # Runs on every push/PR
│   ├── PHP setup + Composer cache
│   ├── php artisan test
│   └── pint --test
└── job: deploy         # Runs ONLY on push to main, needs: [test]
    ├── WIF auth to GCP
    ├── setup-gcloud
    ├── Docker buildx setup
    ├── configure-docker for Artifact Registry
    ├── build + push (SHA tag + latest tag)
    ├── deploy-cloudrun
    ├── sleep 30 (startup wait)
    ├── curl /health (with retry)
    └── rollback on health failure (if step failed)
```

### Pattern 1: Two-Job Workflow (Test Gate + Deploy)

**What:** Split test and deploy into separate jobs. Deploy job uses `needs: [test]` to enforce test gate.
**When to use:** Always — GitHub stops the deploy job if the test job fails. This implements D-01's requirement that failed tests prevent deployment.

```yaml
# Source: github.com/google-github-actions/deploy-cloudrun (v3)
jobs:
  test:
    name: Tests & Lint
    runs-on: ubuntu-latest
    steps:
      # ... existing test steps ...

  deploy:
    name: Build & Deploy
    runs-on: ubuntu-latest
    needs: [test]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    permissions:
      contents: read
      id-token: write   # Required for WIF
    steps:
      # ... deploy steps ...
```

### Pattern 2: Workload Identity Federation Authentication

**What:** Authenticate GitHub Actions to GCP without a service account JSON key using OIDC tokens.
**When to use:** All GCP interactions from GitHub Actions. Preferred over SA keys per Google's own recommendation.

**One-time GCP setup (done by human, not pipeline):**
```bash
# 1. Create workload identity pool
gcloud iam workload-identity-pools create "github-actions" \
  --project="ascent-web-260224-119" \
  --location="global" \
  --display-name="GitHub Actions"

# 2. Create OIDC provider
gcloud iam workload-identity-pools providers create-oidc "github-repo" \
  --project="ascent-web-260224-119" \
  --location="global" \
  --workload-identity-pool="github-actions" \
  --issuer-uri="https://token.actions.githubusercontent.com" \
  --attribute-mapping="google.subject=assertion.sub,attribute.repository=assertion.repository" \
  --attribute-condition="assertion.repository == 'GITHUB_ORG/REPO_NAME'"

# 3. Create CI service account
gcloud iam service-accounts create "github-actions-ci" \
  --project="ascent-web-260224-119" \
  --display-name="GitHub Actions CI"

# 4. Grant SA required roles
gcloud projects add-iam-policy-binding ascent-web-260224-119 \
  --member="serviceAccount:github-actions-ci@ascent-web-260224-119.iam.gserviceaccount.com" \
  --role="roles/artifactregistry.writer"

gcloud projects add-iam-policy-binding ascent-web-260224-119 \
  --member="serviceAccount:github-actions-ci@ascent-web-260224-119.iam.gserviceaccount.com" \
  --role="roles/run.admin"

gcloud projects add-iam-policy-binding ascent-web-260224-119 \
  --member="serviceAccount:github-actions-ci@ascent-web-260224-119.iam.gserviceaccount.com" \
  --role="roles/iam.serviceAccountUser"

# 5. Allow WIF pool to impersonate the SA
gcloud iam service-accounts add-iam-policy-binding \
  "github-actions-ci@ascent-web-260224-119.iam.gserviceaccount.com" \
  --project="ascent-web-260224-119" \
  --role="roles/iam.workloadIdentityUser" \
  --member="principalSet://iam.googleapis.com/projects/528372920943/locations/global/workloadIdentityPools/github-actions/attribute.repository/GITHUB_ORG/REPO_NAME"
```

**Workflow step:**
```yaml
# Source: github.com/google-github-actions/auth (v3)
- uses: google-github-actions/auth@v3
  with:
    workload_identity_provider: ${{ secrets.WIF_PROVIDER }}
    service_account: ${{ secrets.WIF_SERVICE_ACCOUNT }}
```

**GitHub Secrets needed:**
- `WIF_PROVIDER`: Full resource name like `projects/528372920943/locations/global/workloadIdentityPools/github-actions/providers/github-repo`
- `WIF_SERVICE_ACCOUNT`: `github-actions-ci@ascent-web-260224-119.iam.gserviceaccount.com`
- `GCP_PROJECT_ID`: `ascent-web-260224-119` (already in D-15)
- `CLOUD_RUN_SERVICE`: `bite-pos-demo` (already in D-15)
- `GCP_REGION`: `us-central1` (already in D-15)

### Pattern 3: Docker Build with GHA Layer Cache

**What:** Use Docker BuildKit's GHA cache backend to persist build layers between CI runs.
**When to use:** Main deploy job — reduces build time when only application code changes (node_modules, apt packages, Composer vendor cached).

```yaml
# Source: docs.docker.com/build/cache/backends/gha/
- uses: docker/setup-buildx-action@v3

- uses: docker/build-push-action@v6
  with:
    context: .
    push: true
    tags: |
      ${{ env.IMAGE_PATH }}:${{ github.sha }}
      ${{ env.IMAGE_PATH }}:latest
    cache-from: type=gha
    cache-to: type=gha,mode=max
```

Where `IMAGE_PATH` = `us-central1-docker.pkg.dev/ascent-web-260224-119/cloud-run-source-deploy/bite-pos-demo`

### Pattern 4: Post-Deploy Health Check with Auto-Rollback

**What:** After deploying, wait for container startup then hit /health. On failure, roll back traffic to the previous revision.
**When to use:** Every deploy — implements D-05 and D-06.

```bash
# Get the name of the revision that was serving before this deploy
PREV_REVISION=$(gcloud run services describe $SERVICE \
  --region=$REGION --project=$PROJECT_ID \
  --format="value(status.traffic[0].revisionName)" 2>/dev/null || echo "")

# Deploy new revision
gcloud run deploy $SERVICE \
  --image=$IMAGE \
  --region=$REGION \
  --project=$PROJECT_ID

# Wait for container startup + migrations
sleep 30

# Health check with retries
HEALTH_URL="https://bite-pos-demo-xe7go5rfiq-uc.a.run.app/health"
for i in 1 2 3; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL")
  if [ "$STATUS" = "200" ]; then
    echo "Health check passed"
    exit 0
  fi
  echo "Attempt $i: status $STATUS, retrying in 10s..."
  sleep 10
done

# All retries failed — rollback
echo "Health check failed after 3 attempts — rolling back to $PREV_REVISION"
gcloud run services update-traffic $SERVICE \
  --region=$REGION \
  --project=$PROJECT_ID \
  --to-revisions="$PREV_REVISION=100"
exit 1
```

**Note on rollback:** `gcloud run services describe ... --format="value(status.traffic[0].revisionName)"` retrieves the revision currently serving 100% of traffic. Capture this BEFORE deploying. The new deploy creates a new revision and routes all traffic to it. If health check fails, we update-traffic back to the captured revision name.

### Pattern 5: Cloud SQL Backup Configuration

**What:** Enable Cloud SQL automated backups + PITR via `gcloud sql instances patch`.
**When to use:** One-time configuration — run manually from local machine or as a one-time pipeline step.

```bash
# Step 1: Enable sqladmin API (NOT currently enabled — must happen first)
gcloud services enable sqladmin.googleapis.com --project=ascent-web-260224-119

# Step 2: Get the Cloud SQL instance name (find from Cloud Console or Cloud Run env)
INSTANCE_NAME="<cloud-sql-instance-name>"

# Step 3: Enable backups + PITR in one command
gcloud sql instances patch "$INSTANCE_NAME" \
  --project=ascent-web-260224-119 \
  --backup-start-time=02:00 \
  --retained-backups-count=7 \
  --enable-bin-log \
  --retained-transaction-log-days=7

# Step 4: Verify
gcloud sql instances describe "$INSTANCE_NAME" \
  --project=ascent-web-260224-119 \
  --format="yaml(settings.backupConfiguration)"
```

**Expected output after patch:**
```yaml
settings:
  backupConfiguration:
    backupRetentionSettings:
      retainedBackups: 7
      retentionUnit: COUNT
    binaryLogEnabled: true
    enabled: true
    startTime: 02:00
    transactionLogRetentionDays: 7
```

### Anti-Patterns to Avoid
- **Storing GCP service account JSON key as GitHub Secret:** Long-lived credential that can be leaked. Use WIF instead.
- **Using `working-directory: bite` in the workflow:** The repo root IS the app — the subdirectory structure was a historical leftover (D-03, D-04).
- **Path filter `paths: - 'bite/**'`:** Prevents triggers on workflow file changes themselves. Remove entirely (D-03).
- **Running migrations from the pipeline:** The `start.sh` script already handles migrations at container boot. No DB access needed from GitHub Actions.
- **Using inline Docker cache:** Inline cache doesn't support multi-stage builds. Use `type=gha` or registry cache with `mode=max`.
- **Deploying without capturing previous revision first:** If you capture it after deploy, you'll get the new (failing) revision name. Always capture before.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| GCP authentication from GitHub Actions | Custom OIDC token exchange | `google-github-actions/auth@v3` | Handles token exchange, expiry, refresh; official Google implementation |
| Cloud Run deployment | Custom `gcloud run deploy` wrapper | `google-github-actions/deploy-cloudrun@v3` | Parses outputs (`url`, `revision`) needed for subsequent steps |
| Docker multi-stage build + push | Multiple `docker build` + `docker push` commands | `docker/build-push-action@v6` | Handles BuildKit, caching, multi-tag push atomically |
| Docker BuildKit setup | Manual `DOCKER_BUILDKIT=1` env | `docker/setup-buildx-action@v3` | Required for GHA cache backend to work |

**Key insight:** The `google-github-actions/*` suite is maintained by Google and tracks Cloud Run API changes. Rolling custom gcloud scripts risks breakage on API updates.

---

## Common Pitfalls

### Pitfall 1: WIF `id-token: write` permission missing
**What goes wrong:** GitHub Actions OIDC token cannot be requested, WIF auth fails with "Unable to generate GITHUB_TOKEN" or similar.
**Why it happens:** The `id-token: write` permission must be explicitly set on the job (not globally) for WIF to work.
**How to avoid:** Add `permissions: { contents: read, id-token: write }` to the deploy job specifically.
**Warning signs:** Auth step fails with permission error mentioning OIDC token.

### Pitfall 2: Capturing previous revision name after deploy
**What goes wrong:** Rollback sends traffic to the new (failing) revision.
**Why it happens:** Cloud Run immediately routes 100% traffic to the new revision on deploy. If you query `status.traffic[0].revisionName` after deploy, you get the new revision.
**How to avoid:** Always run `gcloud run services describe` to capture the active revision BEFORE the deploy step.
**Warning signs:** Rollback appears to succeed but service remains unhealthy.

### Pitfall 3: sqladmin API not enabled
**What goes wrong:** `gcloud sql instances patch` fails with "API sqladmin.googleapis.com not enabled."
**Why it happens:** The Cloud SQL Admin API is NOT currently enabled on `ascent-web-260224-119` (confirmed by direct probe).
**How to avoid:** Run `gcloud services enable sqladmin.googleapis.com` before any `gcloud sql` commands. Allow 2-5 minutes for propagation.
**Warning signs:** Error message "Cloud SQL Admin API has not been used in project ... before or it is disabled."

### Pitfall 4: Docker authenticate step missing for Artifact Registry
**What goes wrong:** `docker push` fails with authentication error even though gcloud auth succeeded.
**Why it happens:** Docker doesn't automatically use gcloud credentials. Requires explicit `gcloud auth configure-docker <region>-docker.pkg.dev`.
**How to avoid:** After `google-github-actions/auth`, run `gcloud auth configure-docker us-central1-docker.pkg.dev` before any Docker push commands. Alternatively, `docker/build-push-action` handles this when `setup-gcloud` is run first.
**Warning signs:** `unauthorized: authentication required` from Artifact Registry during push.

### Pitfall 5: Health check URL hardcoded vs dynamic
**What goes wrong:** Health check hits wrong URL or fails on first deploy before URL is known.
**Why it happens:** Cloud Run URLs are stable once created, but the `deploy-cloudrun` action outputs the URL as `steps.deploy.outputs.url`.
**How to avoid:** Use `steps.deploy.outputs.url` from the deploy step output, not a hardcoded URL. For the existing service, the URL is `https://bite-pos-demo-xe7go5rfiq-uc.a.run.app` but using the action output is more robust.
**Warning signs:** Health check hits 404 or wrong host.

### Pitfall 6: Cloud SQL backup window during peak hours
**What goes wrong:** Backup window overlaps with POS peak usage (lunchtime in Oman).
**Why it happens:** Default backup window may fall at arbitrary times.
**How to avoid:** Use `--backup-start-time=02:00` (2 AM UTC = 6 AM GST) — before Oman business hours open, minimal POS usage.
**Warning signs:** Slow queries or brief unavailability during backup window reported by users.

### Pitfall 7: WIF propagation delay after setup
**What goes wrong:** First pipeline run after WIF setup fails even though setup commands succeeded.
**Why it happens:** WIF pools, providers, and IAM bindings take up to 5 minutes to propagate globally.
**How to avoid:** Wait 5 minutes after running WIF setup commands before triggering the first pipeline run.
**Warning signs:** Auth step error mentioning "workload identity pool not found" or "permission denied" immediately after setup.

---

## Code Examples

### Complete ci.yml structure (main push — full pipeline)

```yaml
# Source: github.com/google-github-actions/deploy-cloudrun (v3), google-github-actions/auth (v3)
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

env:
  PROJECT_ID: ${{ secrets.GCP_PROJECT_ID }}
  REGION: ${{ secrets.GCP_REGION }}
  SERVICE: ${{ secrets.CLOUD_RUN_SERVICE }}
  IMAGE_PATH: us-central1-docker.pkg.dev/${{ secrets.GCP_PROJECT_ID }}/cloud-run-source-deploy/bite-pos-demo

jobs:
  test:
    name: Tests & Lint
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_sqlite, bcmath, mbstring
          coverage: none
      - name: Get Composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"
      - uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-
      - run: composer install --prefer-dist --no-interaction --no-progress
      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate
      - run: php artisan test
      - run: ./vendor/bin/pint --test

  deploy:
    name: Build & Deploy to Cloud Run
    runs-on: ubuntu-latest
    needs: [test]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    permissions:
      contents: read
      id-token: write
    steps:
      - uses: actions/checkout@v4

      - uses: google-github-actions/auth@v3
        with:
          workload_identity_provider: ${{ secrets.WIF_PROVIDER }}
          service_account: ${{ secrets.WIF_SERVICE_ACCOUNT }}

      - uses: google-github-actions/setup-gcloud@v2

      - name: Capture current revision for rollback
        id: prev-revision
        run: |
          REV=$(gcloud run services describe $SERVICE \
            --region=$REGION --project=$PROJECT_ID \
            --format="value(status.traffic[0].revisionName)" 2>/dev/null || echo "")
          echo "name=$REV" >> "$GITHUB_OUTPUT"

      - uses: docker/setup-buildx-action@v3

      - name: Configure Docker auth for Artifact Registry
        run: gcloud auth configure-docker us-central1-docker.pkg.dev --quiet

      - uses: docker/build-push-action@v6
        with:
          context: .
          push: true
          tags: |
            ${{ env.IMAGE_PATH }}:${{ github.sha }}
            ${{ env.IMAGE_PATH }}:latest
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - id: deploy
        uses: google-github-actions/deploy-cloudrun@v3
        with:
          service: ${{ env.SERVICE }}
          image: ${{ env.IMAGE_PATH }}:${{ github.sha }}
          region: ${{ env.REGION }}

      - name: Wait for container startup
        run: sleep 30

      - name: Post-deploy health check
        id: health-check
        run: |
          HEALTH_URL="${{ steps.deploy.outputs.url }}/health"
          for i in 1 2 3; do
            STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL")
            echo "Attempt $i: HTTP $STATUS"
            if [ "$STATUS" = "200" ]; then
              echo "Health check passed"
              exit 0
            fi
            [ $i -lt 3 ] && sleep 10
          done
          echo "Health check failed after 3 attempts"
          exit 1

      - name: Rollback on health check failure
        if: failure() && steps.prev-revision.outputs.name != ''
        run: |
          echo "Rolling back to ${{ steps.prev-revision.outputs.name }}"
          gcloud run services update-traffic $SERVICE \
            --region=$REGION \
            --project=$PROJECT_ID \
            --to-revisions="${{ steps.prev-revision.outputs.name }}=100"
```

### Cloud SQL Backup Enable Command

```bash
# Source: docs.cloud.google.com/sql/docs/mysql/backup-recovery/configure-pitr
# Step 1: Enable API first (one-time)
gcloud services enable sqladmin.googleapis.com --project=ascent-web-260224-119

# Step 2: Patch instance (replace INSTANCE_NAME with actual Cloud SQL instance name)
gcloud sql instances patch INSTANCE_NAME \
  --project=ascent-web-260224-119 \
  --backup-start-time=02:00 \
  --retained-backups-count=7 \
  --enable-bin-log \
  --retained-transaction-log-days=7

# Step 3: Verify
gcloud sql instances describe INSTANCE_NAME \
  --project=ascent-web-260224-119 \
  --format="yaml(settings.backupConfiguration)"
```

### PITR Restore Command (for reference/runbook)

```bash
# Source: docs.cloud.google.com/sql/docs/mysql/backup-recovery/pitr
# Restores to a clone instance — original remains intact
gcloud sql instances clone SOURCE_INSTANCE_NAME bite-pos-restore \
  --point-in-time '2026-03-27T03:00:00Z' \
  --project=ascent-web-260224-119
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Service account JSON key in GitHub Secrets | Workload Identity Federation (keyless) | 2021-2022 (became standard ~2023) | No long-lived credentials to rotate or leak |
| `docker build` + `docker push` manually | `docker/build-push-action@v6` with BuildKit | 2022+ | Multi-stage caching, atomic multi-tag push |
| `gcloud run deploy` directly in CI | `google-github-actions/deploy-cloudrun@v3` | 2022+ | Structured outputs (URL, revision name) for downstream steps |
| Inline Docker cache | GHA cache backend with `mode=max` | 2023+ | Caches intermediate layers in multi-stage builds |

**Deprecated/outdated:**
- `google-github-actions/auth@v1` / `v2`: Superseded by v3 — use v3.
- `actions/cache@v3`: The legacy GitHub Cache service shuts down April 15, 2025. Use v4.
- Container Registry (`gcr.io`): Deprecated in favour of Artifact Registry (`*.pkg.dev`). Already using Artifact Registry — no change needed.

---

## Open Questions

1. **Cloud SQL instance name**
   - What we know: The Cloud Run service connects to Cloud SQL via Cloud SQL Auth Proxy. The instance connection string is in Cloud Run environment variables (stored as secrets, not visible via gcloud).
   - What's unclear: The exact Cloud SQL instance name in `ascent-web-260224-119` — cannot query via gcloud because `sqladmin.googleapis.com` API is not enabled.
   - Recommendation: Before running the backup patch command, the planner/implementer must find the instance name from the GCP Cloud Console (Cloud SQL section) or from the Cloud Run environment variable `DB_SOCKET` value (format: `/cloudsql/PROJECT:REGION:INSTANCE`).

2. **GitHub repository owner for WIF attribute condition**
   - What we know: WIF provider must be scoped to the specific repository with `attribute.repository == 'OWNER/REPO'`.
   - What's unclear: The exact GitHub organization/owner and repository name (not visible in the codebase).
   - Recommendation: Fill in `GITHUB_ORG/REPO_NAME` in the WIF setup commands using the actual GitHub repository URL.

3. **WIF vs SA Key decision**
   - Claude's discretion item — recommendation is WIF.
   - If WIF setup is perceived as too complex for a solo founder, a service account JSON key stored as `GCP_SA_KEY` GitHub Secret also works with `google-github-actions/auth@v3` using `credentials_json: ${{ secrets.GCP_SA_KEY }}`. WIF is more secure but has a higher one-time setup cost.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|-------------|-----------|---------|----------|
| `gcloud` CLI | Cloud SQL backup config, verification | Yes | 558.0.0 | — |
| Docker | Local image builds and testing | Yes | 28.5.2 | — |
| `iamcredentials.googleapis.com` | WIF authentication | Yes (enabled) | — | Fall back to SA key JSON |
| `artifactregistry.googleapis.com` | Image push | Yes (enabled) | — | — |
| `run.googleapis.com` | Cloud Run deploy | Yes (enabled) | — | — |
| `sqladmin.googleapis.com` | Cloud SQL backup config | **No (disabled)** | — | Must enable before backup commands |
| Artifact Registry repo `cloud-run-source-deploy` | Image storage | Yes (confirmed) | — | — |
| Cloud Run service `bite-pos-demo` | Deploy target | Yes (confirmed) | — | — |
| Health endpoint `/health` | Post-deploy verification | Yes (built Phase 7) | — | — |

**Missing dependencies with no fallback:**
- `sqladmin.googleapis.com` is disabled. Must be enabled via `gcloud services enable sqladmin.googleapis.com` before any Cloud SQL backup configuration can proceed.

**Missing dependencies with fallback:**
- WIF pool and provider do not yet exist — must be created as part of this phase's setup. Fallback: use service account JSON key if WIF setup is skipped.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit via Laravel `php artisan test` |
| Config file | `phpunit.xml` (SQLite in-memory for tests) |
| Quick run command | `php artisan test --filter=HealthCheckTest` |
| Full suite command | `composer test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|--------------|
| DEPLOY-04 | Tests pass before deploy | unit/integration | `php artisan test` (existing suite must pass) | Yes — existing suite |
| DEPLOY-04 | PR triggers test + lint + Docker build only (no push) | manual/smoke | Push a PR and verify Actions run; check no image pushed | Manual only |
| DEPLOY-04 | Push to main triggers full pipeline | manual/smoke | Push to main, verify Actions run deploy job | Manual only |
| DEPLOY-04 | Failed tests block deploy | manual/smoke | Force a test failure, verify deploy job skipped | Manual only |
| DEPLOY-04 | Post-deploy health check passes | smoke | `curl -s https://bite-pos-demo-xe7go5rfiq-uc.a.run.app/health` returns 200 | Manual only |
| SEC-04 | Cloud SQL backups enabled | manual/smoke | `gcloud sql instances describe INSTANCE --format="yaml(settings.backupConfiguration)"` → `enabled: true, binaryLogEnabled: true` | Manual only |

**Note:** DEPLOY-04 and SEC-04 are infrastructure/pipeline requirements. Their verification is primarily smoke testing via live system observation, not automated unit tests. The existing test suite (particularly `HealthCheckTest`) covers the health endpoint that the pipeline depends on.

### Sampling Rate
- **Per task commit:** `php artisan test` (existing suite green)
- **Per wave merge:** `composer test` (full suite with config clear)
- **Phase gate:** Full suite green + live pipeline run succeeds + `gcloud sql instances describe` confirms backups enabled

### Wave 0 Gaps
None — existing test infrastructure covers all phase requirements. The new infrastructure work (workflow YAML, gcloud commands) cannot be unit tested; it is verified by execution.

---

## Project Constraints (from CLAUDE.md)

Directives the planner must verify compliance with:

| Directive | Constraint | Impact on This Phase |
|-----------|------------|----------------------|
| No Tailwind | Do not use Tailwind in any new files | N/A — this phase has no UI |
| No API controllers | Features use Livewire, not API controllers | N/A — no application code |
| Tenant scoping | Every query must be scoped to `shop_id` | N/A — no query changes |
| No existing migration modification | Create new migrations only | N/A — no schema changes |
| Webhook idempotency | Don't bypass `webhook_events` table | N/A — no webhook changes |
| OMR currency | Use `formatPrice()` helper | N/A — no price display |
| Immutability | Always create new objects, never mutate | N/A — no application code |
| 80% test coverage | Minimum coverage across all tests | N/A — pipeline tests are smoke/manual |
| Git commit format | `type: description` conventional commits | Plan tasks must use proper commit types |
| Post-commit Notion update | Update Notion page after every commit | Planner should include as final task |

---

## Sources

### Primary (HIGH confidence)
- `github.com/google-github-actions/auth` — WIF setup steps, workflow YAML, required permissions
- `github.com/google-github-actions/deploy-cloudrun` — deploy action usage, inputs, version (v3)
- `docs.cloud.google.com/sql/docs/mysql/backup-recovery/configure-pitr` — exact `gcloud sql instances patch` flags for PITR

### Secondary (MEDIUM confidence)
- `cloud.google.com/blog/products/devops-sre/deploy-to-cloud-run-with-github-actions` — complete workflow YAML example, IAM roles list
- `docs.docker.com/build/cache/backends/gha/` — GHA cache backend usage for `build-push-action`
- `cloud.google.com/run/docs/rollouts-rollbacks-traffic-migration` — `update-traffic` rollback command syntax
- WebSearch results cross-referenced with official docs: `--retained-backups-count`, `--enable-bin-log`, `--backup-start-time` flags confirmed

### Tertiary (LOW confidence)
- None — all critical claims are verified against official Google/Docker documentation

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all action versions verified from official GitHub repos, gcloud commands from official docs
- Architecture: HIGH — workflow structure confirmed against official examples; rollback pattern confirmed against Cloud Run docs
- Pitfalls: HIGH — sqladmin API disabled confirmed by direct live probe; WIF timing caveat from official docs
- Environment availability: HIGH — directly probed live GCP project

**Research date:** 2026-03-27
**Valid until:** 2026-06-27 (stable domain — GitHub Actions and Cloud Run APIs are stable)

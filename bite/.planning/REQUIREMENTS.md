# Requirements: Bite-POS

**Defined:** 2026-03-26
**Core Value:** Customers can scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line

## v1.2 Requirements

Requirements for production deployment on Google Cloud Run.

### Containerization & Deployment

- [x] **DEPLOY-01**: App runs in a multi-stage Docker container with PHP-FPM + Nginx, Composer deps, and Vite-built assets
- [x] **DEPLOY-02**: App connects to Cloud SQL MySQL 8.0 via Cloud SQL Auth Proxy with proper connection config
- [x] **DEPLOY-03**: Product images and file uploads use Google Cloud Storage filesystem driver instead of local disk
- [ ] **DEPLOY-04**: GitHub Actions workflow runs tests, builds Docker image, pushes to Artifact Registry, and deploys to Cloud Run on push to main

### Production Hardening

- [x] **HARD-01**: Health check endpoint (GET /health) verifies DB connectivity, storage access, GD extension, and queue status
- [x] **HARD-02**: Startup validation fails fast with clear errors if required environment variables are missing
- [x] **HARD-03**: Rate limiting applied to login attempts, webhook endpoints, guest ordering, and API routes
- [x] **HARD-04**: Sentry error tracking configured for production with structured JSON logging for Cloud Logging

### Security & Data Safety

- [ ] **SEC-01**: Tenant isolation audit confirms every database query on tenant data is scoped to shop_id
- [x] **SEC-02**: All secrets managed via Cloud Run environment/secrets — no hardcoded credentials, .env excluded from container
- [ ] **SEC-03**: Input validation sweep covers all user inputs, form submissions, and file uploads for injection/XSS vulnerabilities
- [ ] **SEC-04**: Cloud SQL automated backups enabled with retention policy and point-in-time recovery

## Future Requirements

### Post-Launch

- **POST-01**: Custom domain with SSL via Cloud Run domain mapping
- **POST-02**: Queue worker sidecar or Cloud Tasks for async jobs
- **POST-03**: Cron scheduler via Cloud Scheduler for order expiration and cleanup
- **POST-04**: Email sending for password resets and notifications

## Out of Scope

| Feature | Reason |
|---------|--------|
| Thawani Pay integration | Separate initiative, tracked independently |
| CDN image delivery | Cloud Storage with GCS URLs sufficient for launch |
| Auto-scaling beyond Cloud Run defaults | Premature optimization for first client |
| Multi-region deployment | Single region sufficient for Oman market |
| Kubernetes / GKE | Cloud Run is simpler and sufficient |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DEPLOY-01 | Phase 6 | Complete |
| DEPLOY-02 | Phase 6 | Complete |
| DEPLOY-03 | Phase 6 | Complete |
| DEPLOY-04 | Phase 8 | Pending |
| HARD-01 | Phase 7 | Complete |
| HARD-02 | Phase 7 | Complete |
| HARD-03 | Phase 7 | Complete |
| HARD-04 | Phase 7 | Complete |
| SEC-01 | Phase 7 | Pending |
| SEC-02 | Phase 6 | Complete |
| SEC-03 | Phase 7 | Pending |
| SEC-04 | Phase 8 | Pending |

**Coverage:**
- v1.2 requirements: 12 total
- Mapped to phases: 12
- Unmapped: 0

---
*Requirements defined: 2026-03-26*
*Last updated: 2026-03-26 after roadmap creation*

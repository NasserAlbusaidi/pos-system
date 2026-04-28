# Requirements: Bite-POS

**Defined:** 2026-03-26
**Core Value:** Customers can scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line

## v1.2 Requirements

Requirements for production deployment on Google Cloud Run.

### Containerization & Deployment

- [x] **DEPLOY-01**: App runs in a multi-stage Docker container with PHP-FPM + Nginx, Composer deps, and Vite-built assets
- [x] **DEPLOY-02**: App connects to Cloud SQL MySQL 8.0 via Cloud SQL Auth Proxy with proper connection config
- [x] **DEPLOY-03**: Product images and file uploads use Google Cloud Storage filesystem driver instead of local disk
- [x] **DEPLOY-04**: GitHub Actions workflow runs tests, builds Docker image, pushes to Artifact Registry, and deploys to Cloud Run on push to main

### Production Hardening

- [x] **HARD-01**: Health check endpoint (GET /health) verifies DB connectivity, storage access, GD extension, and queue status
- [x] **HARD-02**: Startup validation fails fast with clear errors if required environment variables are missing
- [x] **HARD-03**: Rate limiting applied to login attempts, webhook endpoints, guest ordering, and API routes
- [x] **HARD-04**: Sentry error tracking configured for production with structured JSON logging for Cloud Logging

### Security & Data Safety

- [x] **SEC-01**: Tenant isolation audit confirms every database query on tenant data is scoped to shop_id
- [x] **SEC-02**: All secrets managed via Cloud Run environment/secrets — no hardcoded credentials, .env excluded from container
- [x] **SEC-03**: Input validation sweep covers all user inputs, form submissions, and file uploads for injection/XSS vulnerabilities
- [ ] **SEC-04**: Cloud SQL automated backups enabled with retention policy and point-in-time recovery *(blocked by GCP Free Trial restriction — backlog)*

## v1.3 Requirements

Requirements for design system unification and brand consistency. Driver: co-founder review by Mohammed; current audit scores 4/10 cross-screen consistency, 3/10 logo, 4/10 typography, 3/10 spacing, 2/10 one-off styling.

### Design Tokens

- [ ] **DS-01**: Typography scale defined as CSS custom properties (--font-size-xs through --font-size-2xl, --font-weight-regular/medium/semibold/bold) and applied across all blade views
- [ ] **DS-02**: Spacing scale defined as CSS custom properties (--space-1 through --space-12 on a consistent ratio) and applied across all blade views and component classes
- [ ] **DS-03**: Single source-of-truth tokens file documented (location, naming convention, ratio rules) so future components can be styled without hardcoding values

### Logo & Brand Identity

- [ ] **DS-04**: x-application-logo Blade component used in all logo placements (guest menu header, admin sidebar, login page, welcome landing, super admin)
- [ ] **DS-05**: Hardcoded "B" badge replaced with x-application-logo in welcome email template; logo size variants (sm/md/lg) supported via component prop
- [ ] **DS-06**: Favicon, manifest icons, and PWA icons regenerated from the canonical logo SVG so they match across browsers and home-screen installs

### Theme Cascade

- [ ] **DS-07**: data-theme attribute (warm/modern/dark) applied to <html> in admin, POS, and super-admin layouts (currently only guest menu)
- [ ] **DS-08**: Admin and POS layouts respond to data-theme — card radius, font weight, accent application, spacing density
- [ ] **DS-09**: Theme overrides for admin/POS documented in app.css alongside existing guest-menu theme blocks (no parallel theme system)

### Email & Print Token Injection

- [ ] **DS-10**: Welcome email template injects shop branding (paper/ink/crema) instead of hardcoding 25+ hex colors; preview render confirms colors update when shop branding changes
- [ ] **DS-11**: Receipt print, invoice print, and shift report templates derive colors from shop branding tokens (with a print-safe fallback when branding is absent)
- [ ] **DS-12**: Shared layout helper for branding injection — single Blade partial used by app.blade.php, admin.blade.php, super-admin layout, email layout, print layout (no duplicate <style> blocks)

### Component Reuse & Style Cleanup

- [ ] **DS-13**: Inline style="..." attributes reduced from 153 to under 30 (allowed only for dynamic computed values like participant colors)
- [ ] **DS-14**: In-blade <style> blocks consolidated — non-print, non-branding-injection blocks moved to app.css component classes; legal/offline pages use shared CSS
- [ ] **DS-15**: Reusable component CSS classes (.surface-card, .loading-spinner, .field, .tag) used in shop-settings, pos-dashboard, shift-report instead of copy-pasted inline styling
- [ ] **DS-16**: Icon style standard documented (outline-only, 1.5px stroke, 24x24 viewBox) and a small set of canonical inline SVG snippets extracted as Blade components for reuse

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
| Dark mode UI (full app dark theme) | v1.3 scope is brand consistency — true dark mode is a separate initiative |
| Storybook / component documentation site | v1.3 establishes the system; documentation site is v1.4+ |
| Accessibility audit (WCAG AA) | Worth its own milestone with proper QA |
| Icon library migration (Heroicons/Lucide) | Inline SVG sufficient with v1.3 standardization; library swap is v1.4 candidate |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DEPLOY-01 | Phase 6 | Complete |
| DEPLOY-02 | Phase 6 | Complete |
| DEPLOY-03 | Phase 6 | Complete |
| DEPLOY-04 | Phase 8 | Complete |
| HARD-01 | Phase 7 | Complete |
| HARD-02 | Phase 7 | Complete |
| HARD-03 | Phase 7 | Complete |
| HARD-04 | Phase 7 | Complete |
| SEC-01 | Phase 7 | Complete |
| SEC-02 | Phase 6 | Complete |
| SEC-03 | Phase 7 | Complete |
| SEC-04 | Phase 9 (gap closure) | Deferred — GCP Free Trial restriction |

**Coverage:**
- v1.2 requirements: 12 total
- Mapped to phases: 12
- Unmapped: 0

### v1.3 Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DS-01, DS-02, DS-03 | Phase 10 | Pending |
| DS-04, DS-05, DS-06 | Phase 11 | Pending |
| DS-07, DS-08, DS-09 | Phase 12 | Pending |
| DS-10, DS-11, DS-12 | Phase 13 | Pending |
| DS-13, DS-14, DS-15, DS-16 | Phase 14 | Pending |

**Coverage:**
- v1.3 requirements: 16 total
- Mapped to phases: 16
- Unmapped: 0

---
*Requirements defined: 2026-03-26*
*Last updated: 2026-04-28 — v1.3 Brand Consistency requirements added (DS-01 through DS-16)*

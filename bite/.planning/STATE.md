---
gsd_state_version: 1.0
milestone: v1.3
milestone_name: Brand Consistency
status: planning
stopped_at: ""
last_updated: "2026-04-30T12:30:00.000Z"
last_activity: 2026-04-30 -- Phase 10.5 context captured; 19 implementation decisions locked across 4 areas (token remap, body bg, admin chrome, hardcoded cleanup)
progress:
  total_phases: 6
  completed_phases: 0
  total_plans: 19
  completed_plans: 0
  percent: 0
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-03-26)

**Core value:** Customers scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line
**Current focus:** v1.3 Brand Consistency — design system unification

## Current Position

Phase: 10.5 (Brand Color Migration) — context captured, ready to plan
Plan: 3 plans estimated (10.5-01 token def + platform mapping, 10.5-02 regression test, 10.5-03 docs)
Status: 10.5-CONTEXT.md committed (9a92c11) — 19 decisions locked across token remap, body background, admin chrome, hardcoded cleanup. Awaiting `/gsd-plan-phase 10.5`. Phase 10 plans still verified and ready (`/gsd-execute-phase 10`).
Last activity: 2026-04-30 — Discussed Phase 10.5: in-place value swap remaps legacy tokens (--paper=#FAFAF7, --crema=#004225, --signal/--focus=#37B34A); body gradient dropped globally; admin.blade.php strips shop-branding injection (admin always Bite green); platform-chrome cleanup limited to 4 theme-color metas + login gradient + admin fallback hex (welcome SVGs/emails/prints/dashboards deferred to Phases 13/14)

Progress: [░░░░░░░░░░░░░░░░] 0% (0/6 phases, 0/19 plans)

## Performance Metrics

**Velocity:**

- v1.0: 2 phases, 5 plans
- v1.1: 3 phases, 6 plans
- v1.2: 4 phases, 9 plans (1 deferred)
- Total shipped: 9 phases, 19 plans

**By Phase:**

| Phase | Duration | Tasks | Files |
|-------|----------|-------|-------|
| Phase 03-item-availability P01 | 132s | 2 | 5 |
| Phase 03-item-availability P02 | ~2m | 2 | 7 |
| Phase 04-image-optimization P01 | 297s | 2 | 7 |
| Phase 04-image-optimization P02 | 157s | 2 | 4 |
| Phase 05-menu-themes P01 | 6m | 2 | 12 |
| Phase 05-menu-themes P02 | 7200s | 2 | 3 |
| Phase 06-containerization-cloud-services P01 | 10m | 3 tasks | 7 files |
| Phase 06-containerization-cloud-services P02 | 4m | 2 tasks | 7 files |
| Phase 07-hardening-security P02 | 600s | 2 tasks | 8 files |
| Phase 07-hardening-security P01 | 187s | 2 tasks | 9 files |
| Phase 07 P03 | 380 | 2 tasks | 7 files |
| Phase 08-ci-cd-data-safety P01 | 159s | 2 tasks | 1 files |
| Phase 09-production-activation-gap-closure P01 | 128s | 2 tasks | 3 files |
| Phase 09-production-activation-gap-closure P02 | 12min | 2 tasks | 0 files |

## Accumulated Context

### Decisions

All decisions logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- [v1.3]: Build on existing 10 color tokens at `resources/css/app.css:153-166` — add typography + spacing alongside, don't replace
- [v1.3]: Vanilla CSS only — Tailwind utilities scattered across blade views are a sweep target in Phase 10 (cataloged) and 14 (replaced)
- [v1.3]: Typography scale must work for both Rubik (Latin) and IBM Plex Sans Arabic — verify EN + AR rendering at every phase
- [v1.3]: Single source of truth for tokens lives in `resources/css/app.css` `:root` block; documented in `docs/design-system.md` (created in Phase 10)
- [v1.3]: Phase 10 blocks Phases 11–14 — every downstream phase consumes the typography + spacing tokens
- [v1.3]: Branding injection consolidated to one Blade partial in Phase 13 — replaces duplicate `<style>` blocks across app/admin/super-admin/email/print layouts
- [Phase 10]: Typography scale = 16px base with rounded whole-px values (xs:12 / sm:14 / base:16 / md:18 / lg:22 / xl:28 / 2xl:34) — anti-aliasing fuzz from sub-pixel sizes on POS hardware ruled out strict 1.25 ratio
- [Phase 10]: Line-height is two-track — `:root` defines Latin (1.20/1.50/1.70); `[lang="ar"]` overrides with +0.15 leading for IBM Plex Sans Arabic
- [Phase 10]: Spacing scale = mixed progression on 4px base (4/8/12/16/20/24/32/40/48/64/80/96) — fine at low end for tight UI, geometric at high end for layout sections
- [Phase 10]: `@apply` calls in app.css component layer (.btn-*, .tag) left untouched — Phase 14 sweep target
- [Phase 10]: Phase 10 fully sweeps inline styles on 4 verification screens only (guest menu, POS dashboard, admin shop settings, super-admin shop list); other screens deferred to Phase 14
- [Phase 10]: Tailwind utility audit list (~1,431 occurrences) becomes a planning artifact `.planning/v1.3-tailwind-sweep-targets.md`, NOT a user-facing doc
- [Phase 10]: Plan 10-02 sweep baseline = 14 literal-value sites (5 guest-menu + 9 shop-settings + 0 pos-dashboard + 0 super-admin/shops); target = 0 via two-pass D-26 grep verification
- [Phase 10]: D-26 verification grep evolved through 2 revisions — original property-name match was mathematically broken; final two-pass form catches dashed sub-properties (`margin-right`, `padding-top`) and packed values (`gap:4px`) while excluding `var(...)` and unit-less zeros
- [v1.3]: Brand pack received 2026-04-30 — 4 logo variants (mark / Latin wordmark / Arabic wordmark / bilingual stacked), 5-color green palette (`#004225` primary → `#B7C40D` lime accent), 9 PNG icons, color scheme PDF — all stored at `resources/brand/`
- [v1.3]: Brand color migration scoped as new Phase 10.5 (between Phase 10 and 11) — keeps verified Phase 10 plans intact; brand colors layer on top
- [v1.3]: Color ownership decision — Bite green is platform chrome (super-admin, admin, billing, login, welcome); per-shop branding (`Shop::branding` JSON) preserves override on tenant-facing routes (`/menu/*`, POS, KDS, receipts) so Sourdough still reads brown/cream on customer-facing screens
- [Phase 11]: Logo component auto-picks variant by locale + size — mark at sm, Latin/Arabic wordmark at md/lg based on `App::getLocale()`, explicit `variant="bilingual"` for login/email
- [Phase 14]: Icon library trace strategy — 9 brand-pack PNGs converted to stroke-based SVGs respecting `currentColor` for theming; shipped as Blade components in `resources/views/components/icons/`
- [Phase 10.5]: In-place token value swap — legacy names (`--paper`/`--ink`/`--crema`/derived neutrals) keep semantic role; values rebind to brand-derived. ~85 component references auto-inherit. Smallest possible diff
- [Phase 10.5]: Forest hierarchy — `--crema` = `#004225` (Bite Primary), `--ink` stays near-black, hover/active uses `--brand-accent-1` (`#37B34A`). `.btn-primary:hover` background changes from `--crema` to `--brand-accent-1`
- [Phase 10.5]: `--paper` = `#FAFAF7` (off-white). Derived neutrals via linear RGB interpolation between paper + ink, same algorithm as `app.blade.php:56-72` shop-branding cascade
- [Phase 10.5]: `--signal` and `--focus` both rebind to `#37B34A` — single brand-green for success states + focus rings. `--alert` keeps red
- [Phase 10.5]: `--brand-accent-2` (`#7AC70C`) and `--brand-accent-3` (`#B7C40D`) defined in `:root`, no chrome role in v1.3 (reserved for charts/badges/v1.4)
- [Phase 10.5]: `--brand-secondary` (`#0B6B2E`) defined in `:root`, no chrome role (reserved for Phase 12 themes)
- [Phase 10.5]: Body gradient dropped globally — `body { background: rgb(var(--paper)); }` replaces multi-stop radial+linear. `body::before` noise dot pattern preserved. Tenants still feel right because shop branding overrides `--paper`
- [Phase 10.5]: `admin.blade.php` strips shop-branding inline `<style>` injection (lines 14–41 removed). Admin always shows Bite green. Color picker on `/admin/shop-settings` becomes a SCOPED preview (own inline `<style>` on a wrapper div)
- [Phase 10.5]: `app.blade.php` shop-branding injection (lines 28–89) UNCHANGED. Tenant routes (`/menu/*`, POS, KDS) keep per-shop cascade
- [Phase 10.5]: Plan 10.5-02 = Laravel feature test asserting GET `/menu/{slug}` HTML contains shop-specific `<style>` block + GET `/dashboard` does NOT contain shop-branding `<style>` block. Lives in `tests/Feature/BrandingCascadeTest.php`
- [Phase 10.5]: Hardcoded cleanup scope = platform chrome only — 4 `<meta name="theme-color">` tags `#EC6D2E/#EC692E` → `#004225`, `guest.blade.php` login gradient hardcoded RGBA → brand-derived, `admin.blade.php` fallback hex literals removed (dead code after injection strip). Welcome SVGs (Phase 14), emails (Phase 13), prints (Phase 13), dashboard shadows (Phase 14), reports chart literals (Phase 14) all DEFERRED

### Pending Todos

None — ready to plan Phase 10.5 (or execute Phase 10 first; the two are independent until 10.5-01 starts editing `:root`).

### Blockers/Concerns

- None for v1.3. Phase 10 unblocks the rest of the milestone. Phase 10.5 depends on Phase 10 shipping first (typography + spacing tokens land in same `:root` block).

## Session Continuity

Last session: 2026-04-30T12:30:00.000Z
Stopped at: Phase 10.5 context captured — 19 decisions locked, ready for planning
Resume file: .planning/phases/10.5-brand-color-migration/10.5-CONTEXT.md → next action `/gsd-plan-phase 10.5` (or `/gsd-execute-phase 10` first if Phase 10 not yet shipped)

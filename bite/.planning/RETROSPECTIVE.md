# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — Sourdough Demo

**Shipped:** 2026-03-21
**Phases:** 2 | **Plans:** 5 | **Tasks:** 9
**Timeline:** 2 days (2026-03-19 → 2026-03-21)
**Commits:** 45 | **Files:** 93 changed | **Lines:** +7,185 / -526

### What Was Built
- Guest menu visual overhaul: 2-col grid, accordion expand, shimmer loading, broken-image fallback, /storage/ URL fix
- Full 8-token branding cascade derived from 3 brand colors via PHP linear RGB interpolation
- Playfair Display self-hosted serif font for category headers
- Sourdough Oman demo shop: 33 bilingual items, 6 categories, warm parchment branding
- 6 regression tests (2 branding + 4 smoke tests), 20 total guest menu tests passing
- End-to-end flow verified: guest menu → order → KDS

### What Worked
- **Phase separation (code vs data)** — Phase 1 was pure code polish, Phase 2 was pure data entry. Clean boundary, no cross-contamination.
- **Plan checker caught real issues** — D-01/D-04 contradictions with the seeder approach were flagged before execution, saving a wasted cycle.
- **Human verification checkpoints** — Having the user walk the actual flow before signing off caught what automated tests couldn't: the *feel* of the branding cascade.
- **Idempotent seeder** — SourdoughMenuSeeder guards against duplicate runs, making demo resets trivial.

### What Was Inefficient
- **Snap-to-Menu referenced but not built** — CONTEXT.md referenced files that don't exist (`app/Livewire/SnapToMenu.php`). The planner had to discover this at plan time and work around it.
- **D-01/D-04 locked decisions needed unlocking** — The discuss-phase locked "manual admin panel" and "use Snap-to-Menu" as decisions, but neither was executable by agents. One plan-check-revise cycle was spent resolving this.
- **DEMO-02 photo ambiguity** — Requirement said "photos" but decision said "skip photos." Needed explicit user disambiguation.

### Patterns Established
- **Seeder pattern for demo data** — `SourdoughMenuSeeder` is a reusable pattern for pre-building client demos. Use `forceCreate()` or explicit `$model->shop_id` assignment for guarded fields.
- **Branding cascade** — 3 hex colors → 8 CSS tokens via `$mix()` PHP helpers in `app.blade.php`. Any new shop just sets 3 colors and gets a warm theme.
- **Test strategy** — `Livewire::test()` for component assertions, `$this->get()` for layout-level assertions (CSS tokens in `app.blade.php`).

### Key Lessons
- Lock decisions that are actually achievable. "Use Snap-to-Menu" shouldn't be locked if Snap-to-Menu doesn't exist.
- Requirements and decisions can conflict — surface the conflict early (during discuss-phase) rather than discovering it during plan verification.
- Data phases are fast. Phase 2 was mostly a seeder + verification — no architectural complexity.

### Cost Observations
- Model mix: ~60% sonnet (executors, checkers, verifier), ~40% opus (planner, orchestration)
- Sessions: 3 (planning × 2 phases + execution)
- Notable: Phase 2 planning required 2 checker iterations due to decision conflicts; otherwise smooth

---

## Cross-Milestone Trends

| Metric | v1.0 |
|--------|------|
| Phases | 2 |
| Plans | 5 |
| Tasks | 9 |
| Days | 2 |
| Commits | 45 |
| Files changed | 93 |
| Lines added | 7,185 |
| Tests added | 6 |
| Checker iterations | 2 (Phase 2 only) |

---

*Last updated: 2026-03-21 after v1.0*

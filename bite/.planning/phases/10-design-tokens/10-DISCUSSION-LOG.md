# Phase 10: Design Tokens — Discussion Log

**Discussed:** 2026-04-28
**Mode:** default (single-question turns, multi-question batches)
**Phase:** 10-design-tokens

---

## Pre-Discussion State

- ROADMAP.md Phase 10 already drafted with 3 plans, success criteria, and DS-01/DS-02/DS-03 mapping
- STATE.md decisions log: build on existing color tokens (don't replace); vanilla CSS direction; Rubik + IBM Plex Sans Arabic verification required at every phase; tokens live in `resources/css/app.css :root`
- Existing `app.css`: 12 color tokens at line 153, 14 `@apply` usages in component layer, `@tailwind` directives still active, 1,431 Tailwind utility occurrences across blade views
- No prior CONTEXT.md for v1.3; Phase 09 CONTEXT.md used as format reference

## Gray Areas Presented

User selected ALL FOUR offered areas:
1. Scale values & line-heights
2. Tailwind / `@apply` policy in `app.css`
3. Phase 10 sweep boundary
4. Documentation depth & structure

---

## Round 1 — Scale Values, @apply Policy, Sweep Scope, Docs

### Q1: Typography base size + 1.25 ratio scale
**Options presented:**
- 16px base, rounded whole-px values (Recommended)
- 16px base, strict 1.25 (sub-pixel)
- 14px base, dense
- 15px base, balanced

**Selected:** 16px base, rounded whole-px values
**Reasoning surfaced:** Anti-aliasing fuzz on POS hardware from prior testing → rounded whole-px is safer; guest menu is consumer-facing → 16px base is friendlier than POS-tool-style 14px.

**Captured as:** D-05, D-06

### Q2: `@apply` policy for existing component classes
**Options presented:**
- Leave alone, catalog for Phase 14 (Recommended)
- Strip all `@apply` in Phase 10
- Strip only `.btn-*` family

**Selected:** Leave alone, catalog for Phase 14
**Reasoning surfaced:** Phase 10's job is establishing tokens, not refactoring components. Keeps blast radius small. Phase 14 (DS-14, DS-15) is the natural home for the sweep.

**Captured as:** D-14, D-15, D-16

### Q3: Sweep aggressiveness on the 4 verification screens
**Options presented:**
- Full sweep on the 4 screens (Recommended)
- Tokens only + global default rules
- Full sweep on 4 screens + adjacent shared partials

**Selected:** Full sweep on the 4 screens
**Reasoning surfaced:** ROADMAP success criteria #2 and #3 literally require zero hardcoded values on those four screens. "Tokens only" would force a roadmap criteria edit. Adjacent partials defer to Phase 14 to keep scope tight.

**Captured as:** D-17, D-18, D-19

### Q4: `docs/design-system.md` scope
**Options presented:**
- Lean reference, expand per-phase (Recommended)
- Comprehensive upfront
- Tokens only, no narrative

**Selected:** Lean reference, expand per-phase
**Reasoning surfaced:** Phase 12, 13, 14 each append their own section as they ship — avoids speculative content. Pragmatic for solo dev velocity.

**Captured as:** D-20, D-21, D-22, D-23

---

## Round 2 — Spacing Progression & Arabic Line-Height

### Q5: 12-step spacing progression on 4px base
**Options presented:**
- Mixed: fine at low end, geometric at high end (Recommended)
- Linear 4px steps (capped at 48px)
- Geometric / Tailwind-like (4-8-12-16-24-32-48-64-...)

**Selected:** Mixed
**Reasoning surfaced:** Realistic UI range covered without composition or magic numbers. Linear caps too low; geometric is too coarse in the 16-48 zone where most UI lives.

**Captured as:** D-10, D-11

### Q6: Arabic line-height handling
**Options presented:**
- Two-track with `[lang="ar"]` override (Recommended)
- Single-track only
- Bake line-height into size tokens

**Selected:** Two-track with `[lang="ar"]` override
**Reasoning surfaced:** IBM Plex Sans Arabic glyph metrics (taller ascenders) require more leading than Rubik. Bite-POS has heavy Arabic content (guest menu, receipts, customer flows). Single-track risks clipped descenders. Bake-into-size breaks override flexibility.

**Captured as:** D-08, D-09

---

## Auto-Captured (Locked by ROADMAP, no question asked)

- Token name conventions (t-shirt for type sizes, semantic for weights, numeric for spacing) — D-01, D-02, D-03
- Font weight mapping to CSS values 400/500/600/700 — D-07
- Tokens go in `@layer base { :root }` block at line 153 — D-12, D-13
- Verification at 360px viewport for both locales — D-09, D-24

---

## Claude's Discretion (Granted)

- Token ordering within `:root` (alphabetical vs grouped — pick whichever reads cleanest)
- Inline comment style for new token blocks in `app.css`
- Whether to add cross-reference comment to `docs/design-system.md` from `app.css`
- Wording of the do/don't example
- Tailwind audit script output format (CSV / Markdown table / grouped-by-file)
- Tooling for the audit (grep + awk vs PHP script)
- Sweep order (per-screen sequential vs find-and-replace pass)

---

## Deferred (Captured but out of Phase 10 scope)

- Rip out Tailwind entirely from `app.css` → Phase 14
- Sweep inline styles on screens beyond the four → Phase 14
- Container queries / fluid type → v1.4+
- Dark mode UI as a true theme → separate initiative
- Storybook / token preview page → v1.4+
- Icon library swap → v1.4+
- Accessibility audit → separate milestone
- Component class library → Phase 14
- Theme cascade to admin/POS/super-admin → Phase 12
- Branding injection partial → Phase 13
- Logo component → Phase 11

---

## Scope Creep Avoided

None — user kept discussion within Phase 10 boundaries; deferred items emerged organically from already-roadmapped phases.

---

## Outcome

Six question rounds, two AskUserQuestion turns. All decisions converged on recommended options. CONTEXT.md captures 26 implementation decisions (D-01 through D-26) covering naming conventions, exact scale values, line-height bilingual strategy, sweep boundaries, documentation scope, and verification approach.

Ready for `/gsd-plan-phase 10`.

---

*Phase: 10-design-tokens*
*Discussion logged: 2026-04-28*

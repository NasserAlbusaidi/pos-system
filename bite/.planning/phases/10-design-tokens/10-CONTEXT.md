# Phase 10: Design Tokens - Context

**Gathered:** 2026-04-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Establish typography and spacing scales as CSS custom properties in `resources/css/app.css`, alongside the existing 12 color tokens already defined at line 153. Apply the new tokens across four verification screens — guest menu, POS dashboard, admin shop settings, super-admin shop list — so that hardcoded `style="font-size:..."` and `style="padding:..."` values on those screens drop to zero. Document the system as the single source of truth in a new `docs/design-system.md`.

Out of scope for this phase: the inline-style sweep on screens beyond the four verification targets (Phase 14), the `@apply` rewrite of existing component classes (Phase 14), the theme cascade extension to admin/POS (Phase 12), the email/print branding partial (Phase 13), and the logo component (Phase 11).

</domain>

<decisions>
## Implementation Decisions

### Token Naming Convention
- **D-01:** Typography sizes use t-shirt naming (`--font-size-xs/sm/base/md/lg/xl/2xl`). Locked by ROADMAP.md.
- **D-02:** Font weights use semantic naming (`--font-weight-regular/medium/semibold/bold`). Locked by ROADMAP.md.
- **D-03:** Spacing uses numeric naming (`--space-1` … `--space-12`). Locked by ROADMAP.md.
- **D-04:** Line-heights use semantic naming (`--line-height-tight/normal/loose`).

### Typography Scale
- **D-05:** Base font size = 16px (browser default, comfortable at desktop POS and 360px guest menu).
- **D-06:** Scale uses **rounded whole-px values** prioritizing render crispness over strict 1.25 ratio:
  ```
  --font-size-xs:    12px
  --font-size-sm:    14px
  --font-size-base:  16px
  --font-size-md:    18px
  --font-size-lg:    22px
  --font-size-xl:    28px
  --font-size-2xl:   34px
  ```
- **D-07:** Font weights map directly to CSS weight values:
  ```
  --font-weight-regular:   400
  --font-weight-medium:    500
  --font-weight-semibold:  600
  --font-weight-bold:      700
  ```

### Line-Height Strategy (Bilingual)
- **D-08:** Two-track system — `:root` defines Latin line-heights; `[lang="ar"]` selector overrides with +0.15 leading for IBM Plex Sans Arabic, which has taller ascenders than Rubik:
  ```
  :root {
    --line-height-tight:  1.20;
    --line-height-normal: 1.50;
    --line-height-loose:  1.70;
  }
  [lang="ar"] {
    --line-height-tight:  1.35;
    --line-height-normal: 1.65;
    --line-height-loose:  1.85;
  }
  ```
- **D-09:** Verification at 360px viewport for both `App::setLocale('en')` and `App::setLocale('ar')` is required by ROADMAP success criterion #5 — neither Rubik nor IBM Plex Sans Arabic should overflow or clip.

### Spacing Scale
- **D-10:** 12-step **mixed progression** (fine-grained at low end, geometric at high end) on a 4px base:
  ```
  --space-1:   4px    (tight icon gap)
  --space-2:   8px    (small gap)
  --space-3:   12px   (default text spacing)
  --space-4:   16px   (default block padding)
  --space-5:   20px
  --space-6:   24px   (card padding)
  --space-7:   32px   (section gap)
  --space-8:   40px
  --space-9:   48px   (hero padding)
  --space-10:  64px
  --space-11:  80px
  --space-12:  96px   (page-section gap)
  ```
- **D-11:** This range covers realistic UI usage — 4-24px for tight UI elements, 32-96px for layout sections. No `--space-0` (use omission); no values above 96px (use composition or document the magic number).

### Token File Location & Structure
- **D-12:** New tokens go inside the existing `@layer base { :root }` block in `resources/css/app.css` (line 153), immediately following the color tokens. Order: colors → typography sizes → font weights → line-heights → spacing.
- **D-13:** Do NOT migrate tokens out of `@layer base` to plain `:root`. Keep the existing Tailwind `@layer` wrapper untouched in Phase 10.

### Tailwind / @apply Policy
- **D-14:** **Leave existing `@apply` calls alone.** The 14 `@apply` occurrences in `app.css` (`.btn-primary`, `.btn-secondary`, `.btn-danger`, `.tag`, `body`, `h1-h4`, `[x-cloak]`) remain as-is in Phase 10. They are catalogued for the Phase 14 component-class refactor (DS-14, DS-15).
- **D-15:** Do NOT rewrite `.btn-*` or `.tag` component classes in Phase 10. Phase 10 only ADDS new tokens to `:root`; it does not refactor existing components.
- **D-16:** **Tailwind utility audit deliverable** — Phase 10's plan 10-03 produces a separate planning artifact `.planning/v1.3-tailwind-sweep-targets.md` (not in `docs/`), listing every blade view containing Tailwind utility classes (`text-`, `p-`, `m-`, `gap-`, `flex`, `grid`, etc.) with line counts and a category tag (extractable / dynamic-required). This file feeds Phase 11–14 sweep work. ~1,431 occurrences expected based on initial scout.

### Phase 10 Sweep Boundary
- **D-17:** **Full sweep on the four verification screens.** Replace every `style="font-size:..."`, `style="padding:..."`, `style="margin:..."`, and `style="gap:..."` on guest menu, POS dashboard, admin shop settings, and super-admin shop list with `var(--font-size-*)` / `var(--space-*)` references. Required by ROADMAP success criteria #2 and #3 ("reduced to zero on those four screens").
- **D-18:** Other screens (KDS, billing, super-admin user management, etc.) keep their inline styles in Phase 10 — they are Phase 14's sweep targets.
- **D-19:** Shared partials pulled in by the four screens (header, sidebar, layout shells) are NOT swept in Phase 10 unless an inline style is the direct cause of a verification screen failing the Success Criteria. This keeps Phase 10's blast radius small.

### Documentation Scope
- **D-20:** `docs/design-system.md` is **lean reference** — token tables, ratio rationale, one do/don't pair, contributor rule. Target ~80–120 lines. Sibling to `docs/ARCHITECTURE.md`, `docs/DEPLOYMENT.md`, `docs/OPERATIONS.md`.
- **D-21:** Document layout (top to bottom):
  1. Source of truth statement (location: `resources/css/app.css :root`)
  2. Color tokens (existing — table only)
  3. Typography tokens (with 1.25 ratio rationale + 16px base rationale)
  4. Font weight tokens
  5. Line-height tokens (two-track with Arabic override explanation)
  6. Spacing tokens (with 4px base + mixed progression rationale)
  7. One do/don't pair: ✅ `padding: var(--space-4)` vs ❌ `padding: 16px`
  8. Contributor rule: "New components MUST consume tokens; new tokens MUST be added to `:root`, never inlined"
- **D-22:** Phase 12 will append a "Themes" section, Phase 13 will append "Branding Injection", Phase 14 will append "Components". Don't pre-empt those sections in Phase 10.
- **D-23:** Do NOT include the Tailwind audit target list inside `docs/design-system.md`. That goes to the planning artifact in D-16.

### Verification Strategy
- **D-24:** Verification per ROADMAP success criteria runs on `/menu/sourdough` (guest menu, AR locale toggle), `/dashboard` (POS dashboard), `/admin/shop-settings` (admin shop settings), `/super-admin/shops` (super admin shop list). Both EN and AR locales must render guest menu without overflow at 360px viewport.
- **D-25:** Token presence verification uses `grep -nE '\-\-font-size-|\-\-space-|\-\-font-weight-|\-\-line-height-' resources/css/app.css` to confirm new tokens in `:root`. Expected counts: 7 `--font-size-*` + 4 `--font-weight-*` + 12 `--space-*` + 3 Latin `--line-height-*` (in `:root`) + 3 Arabic `--line-height-*` (in `[lang="ar"]`) = 29 token occurrences total.
- **D-26:** Inline-style verification uses a **two-pass property:literal grep** that catches dashed sub-properties (`margin-right`, `padding-top`, etc.), packed values (`gap:4px`), and ignores `var(...)` references. Pass 1 extracts lines containing `style="..."`; Pass 2 checks for any `(prop)\s*:\s*[number][unit]` pair where prop ∈ the locked list:
  ```bash
  PROPS='\b(font-size|padding|padding-(top|right|bottom|left)|margin|margin-(top|right|bottom|left)|gap|row-gap|column-gap)\s*:\s*[0-9]+(\.[0-9]+)?(px|rem|em|%)'

  grep -En 'style="[^"]*"' \
    resources/views/livewire/guest-menu.blade.php \
    resources/views/livewire/pos-dashboard.blade.php \
    resources/views/livewire/shop-settings.blade.php \
    resources/views/livewire/super-admin/shops/index.blade.php \
    | grep -E "$PROPS"
  ```
  **What this matches:** `padding: 12px`, `margin-top:4px`, `gap:4px`, `font-size: 0.85rem`, packed or spaced values, dashed sub-properties.
  **What this excludes:** `padding: var(--space-3)` (var() reference), `padding: 0` (no unit), `border-radius: 10px` (not in prop list, so a sibling `border-radius` doesn't trigger the line when the only `padding:` value is `0`).
  **Verified baseline (2026-04-28):** guest-menu: 5 matches, pos-dashboard: 0, shop-settings: 9, super-admin/shops/index: 0 — total 14 literal-value sites to sweep. Expected after Plan 10-02 sweep: zero matches.
- **D-26b:** Rationale — the original property-name grep matched the property name regardless of value, so substitution didn't reduce matches. The single-pass `[^v"][^"]*[0-9]+` variant missed dashed sub-properties (`margin-right:`) and zero-whitespace packed values (`gap:4px`). The two-pass approach above is correct: Pass 1 isolates style attributes, Pass 2 checks each candidate property:literal pair directly. This preserves the locked scope (D-14, D-15, D-17): no inline `style=""` extraction to CSS classes; just literal-to-token replacement on the four verification screens.

### Claude's Discretion
- Exact ordering of new token blocks within `:root` (alphabetical vs grouped — pick whichever reads cleanest)
- Inline comment style above each token group in `app.css` (single-line vs banner)
- Whether to add a brief `/* Typography Scale — see docs/design-system.md */` cross-reference comment in `app.css`
- Wording of the do/don't example in `docs/design-system.md`
- Whether the Tailwind audit script for plan 10-03 outputs CSV, Markdown table, or grouped-by-file list — pick whatever reads best for downstream phase planners
- Tooling for the audit (grep + awk vs a small PHP script in `bin/`)
- Order of operations within the sweep (per-screen sequential vs find-and-replace pass across all four)

</decisions>

<specifics>
## Specific Ideas

- "Build on the existing color tokens, don't replace them" — confirmed via STATE.md decision log; tokens at `resources/css/app.css:153-166` are the anchor.
- "Vanilla CSS only" is the v1.3 direction, but pragmatically Phase 10 leaves Tailwind in `app.css` alone — sweep is Phase 14's job. Don't conflate "no new Tailwind" with "rip out existing Tailwind."
- Reference: Linear, Notion, and Stripe Dashboard sit at ~14px base; Bite-POS chose 16px because the guest menu is consumer-facing and benefits from a friendlier reading size.
- IBM Plex Sans Arabic glyph metrics are the reason for the two-track line-height system — without it, Arabic text feels cramped at 1.5 leading even when Rubik feels fine at the same value.
- The 1,431 inline Tailwind utility occurrences across blade views are NOT touched in Phase 10. Cataloging only.

</specifics>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase Definition & Requirements
- `.planning/ROADMAP.md` §"Phase 10: Design Tokens" — Goal, success criteria, plan breakdown (10-01, 10-02, 10-03), DS-01/DS-02/DS-03 requirement mapping
- `.planning/REQUIREMENTS.md` §"v1.3 Requirements › Design Tokens" — DS-01, DS-02, DS-03 requirement text
- `.planning/PROJECT.md` — Project context, milestone v1.3 scope, key decisions log

### Existing Token Source (Edit Target)
- `resources/css/app.css` lines 152–166 — Existing `@layer base { :root }` block with 12 color tokens. New tokens go IMMEDIATELY AFTER the color block.
- `resources/css/app.css` lines 1–148 — Font-face declarations for Rubik, IBM Plex Sans Arabic (4 weights, EN + AR subsets), JetBrains Mono. Confirms which fonts are loaded.
- `resources/css/app.css` lines 200–250 — Existing `@layer components` block with `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.tag` using `@apply`. **Do NOT modify in Phase 10.**
- `resources/css/app.css` lines 634–712 — Existing `[data-theme="warm|modern|dark"]` blocks (currently guest-menu only). Untouched in Phase 10; extended in Phase 12.

### Verification Screens
- `resources/views/livewire/guest-menu.blade.php` — Guest menu (EN + AR locale verification)
- `resources/views/livewire/pos-dashboard.blade.php` — POS dashboard
- `resources/views/livewire/shop-settings.blade.php` — Admin shop settings
- `resources/views/super-admin/shops.blade.php` (or similar) — Super-admin shop list — **researcher must confirm exact path**

### Documentation Targets
- `docs/ARCHITECTURE.md` — Existing sibling doc (style/voice reference)
- `docs/DEPLOYMENT.md` — Existing sibling doc (style/voice reference)
- `docs/OPERATIONS.md` — Existing sibling doc (style/voice reference)
- `docs/design-system.md` — **NEW in Phase 10** (~80–120 lines per D-21)

### Planning Artifacts (Created by Phase 10)
- `.planning/v1.3-tailwind-sweep-targets.md` — **NEW** Tailwind utility audit feeding Phase 11–14

### Prior Phase Context
- `.planning/phases/09-production-activation-gap-closure/09-CONTEXT.md` — Style/voice reference for CONTEXT.md format
- `.planning/STATE.md` §"Decisions" — v1.3 architectural decisions already locked

### Project Conventions
- `CLAUDE.md` §"Tech Stack" — "Vanilla CSS with CSS custom properties (design tokens). Do NOT use Tailwind." (Caveat: existing app.css `@apply` is grandfathered; sweep is Phase 14)
- `CLAUDE.md` §"Localization (en/ar)" — Both EN and AR must work; affects line-height verification

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **Existing `:root` block** at `resources/css/app.css:153` — 12 color tokens already structured as space-separated RGB triples for `rgb(var(--token) / alpha)` syntax. New tokens follow the same structural pattern (px values, not RGB).
- **Existing `[lang="ar"]` selector pattern** — Already used in `app.css` for Arabic font-family overrides; reused for line-height override.
- **HasTranslations trait + locale switching** (`App::setLocale()`) — Already wired; Phase 10 verification just renders both locales, no new code.
- **`docs/` folder structure** — ARCHITECTURE.md / DEPLOYMENT.md / OPERATIONS.md establish naming and tone conventions for `docs/design-system.md`.

### Established Patterns
- **CSS custom properties as the single source of truth** — Color tokens prove this works. Typography + spacing extend the proven pattern.
- **Vanilla CSS + design tokens** is the project's stated direction (CLAUDE.md). Phase 10 enforces it for new code; doesn't retro-fit old code.
- **`@layer base { :root }` is the token home** — Tailwind's layer mechanism is preserved because Tailwind is still loaded; new tokens stay in this layer.
- **EN + AR parity** — Every customer-facing field has `_en`/`_ar` columns; every text token must render correctly in both scripts.

### Integration Points
- **Phase 11 consumes `--space-*`** for logo size variants (`<x-application-logo size="sm|md|lg" />`)
- **Phase 12 consumes `--font-size-*`, `--font-weight-*`, `--space-*`** for theme cascade overrides on admin/POS
- **Phase 13 consumes `--font-size-*`, `--space-*`** in branding injection partial; emails/prints reference these tokens
- **Phase 14 consumes everything** — sweep replaces inline styles with token references; component classes (`.surface-card`, `.field`) defined using these tokens

### Known Constraints
- 1,431 Tailwind utility occurrences in blade views — DO NOT touch in Phase 10 (Phase 14 sweep target)
- 14 `@apply` calls in app.css component layer — DO NOT touch in Phase 10 (Phase 14 sweep target)
- Sub-pixel font sizes (e.g., 12.8px from strict 1.25 ratio) caused anti-aliasing fuzz on POS hardware in prior testing — rounded whole-px scale (D-06) avoids this

</code_context>

<deferred>
## Deferred Ideas

- **Rip out Tailwind entirely from app.css** (replace `@tailwind` directives + 14 `@apply` calls in `.btn-*`, `.tag`, `body`, `h1-h4`) — defer to Phase 14 (DS-14: in-blade `<style>` consolidation also covers this)
- **Sweep inline styles across KDS, billing, super-admin user management, all other non-verification screens** — defer to Phase 14 (DS-13)
- **Container queries / fluid type** (e.g., `clamp()` for responsive sizing) — out of v1.3 scope; potential v1.4 enhancement
- **Dark mode UI as a true theme** — explicitly out-of-scope per REQUIREMENTS.md (separate initiative)
- **Storybook / token preview page** — explicitly out-of-scope per REQUIREMENTS.md (v1.4+)
- **Icon library swap** (Heroicons/Lucide replacing inline SVG) — explicitly out-of-scope per REQUIREMENTS.md
- **Accessibility audit (WCAG AA contrast/sizing)** — explicitly out-of-scope per REQUIREMENTS.md (separate milestone)
- **Component class library** (`.surface-card`, `.field`, `.tag`, `.loading-spinner`) — Phase 14 (DS-15)
- **Theme cascade extension to admin/POS/super-admin** — Phase 12 (DS-07, DS-08, DS-09)
- **Branding injection partial for emails/prints** — Phase 13 (DS-10, DS-11, DS-12)
- **Logo component (`<x-application-logo>`) consuming `--space-*`** — Phase 11 (DS-04, DS-05, DS-06)

</deferred>

---

*Phase: 10-design-tokens*
*Context gathered: 2026-04-28*

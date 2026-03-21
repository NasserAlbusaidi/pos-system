# Phase 3: Item Availability - Context

**Gathered:** 2026-03-21
**Status:** Ready for planning

<domain>
## Phase Boundary

Operators can mark items sold out in real time; guests see accurate availability on the menu. Admin toggles products available/unavailable from the menu builder (ProductManager). Guest menu shows unavailable products greyed out with a badge instead of hiding them. Cart validates item availability at checkout.

</domain>

<decisions>
## Implementation Decisions

### Sold-Out Display (Guest Menu)
- **D-01:** Unavailable products shown greyed out with a "Sold Out" badge overlaying the product image area (diagonal ribbon or overlay)
- **D-02:** Items stay in their original sort position within the category — not pushed to bottom
- **D-03:** Claude's discretion on whether greyed-out cards are tappable (show details but disable "Add to cart") or fully locked
- **D-04:** Claude's discretion on exact opacity level for greyed-out treatment

### Admin Toggle UX (ProductManager)
- **D-05:** Availability toggle appears in BOTH places — inline toggle on the product list row (one-click) AND inside the product edit form
- **D-06:** Use simple language: "Available" / "Sold Out" — not the "86'd" restaurant slang (POS dashboard keeps its existing 86'd terminology)
- **D-07:** Reuse the existing `toggle86()` logic from PosDashboard (same underlying `is_available` column flip + audit log)

### Cart Error Recovery (Guest Menu)
- **D-08:** Claude's discretion on the cart recovery flow when stale sold-out items are found at checkout — backend catch already exists in GuestMenu.php (lines 669-691), frontend UX needs design

### Claude's Discretion
- Tappable vs fully locked greyed-out cards (D-03)
- Exact opacity for greyed-out treatment (D-04)
- Cart error recovery UX pattern (D-08)
- Badge visual design (ribbon vs overlay vs banner)
- Loading/transition animations when availability changes

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Item Availability (existing code)
- `app/Models/Product.php` — `is_available` in `$fillable`, column already exists
- `app/Livewire/PosDashboard.php` — `toggle86()` method (lines 118-137): toggles `is_available`, audit logs, toast
- `app/Livewire/GuestMenu.php` — Lines 492, 672, 856, 1014: currently filters `is_available = true` (hides unavailable); Lines 669-691: cart validation catches unavailable items at checkout
- `app/Livewire/ProductManager.php` — Currently has NO availability toggle, needs one added
- `database/migrations/2026_02_01_064605_add_sorting_and_visibility_to_products.php` — Migration that added `is_available` column

### Translations
- `lang/en/guest.php` — `items_unavailable` key exists for cart error messages
- `lang/ar/guest.php` — Arabic translation for unavailable items

### Research
- `.planning/research/FEATURES.md` — Item availability feature landscape
- `.planning/research/ARCHITECTURE.md` — Integration architecture for sold-out toggle
- `.planning/research/PITFALLS.md` — Pitfalls for availability feature (stale cart UX)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `PosDashboard::toggle86()` — Complete toggle logic with audit logging and toast notifications; can be extracted or mirrored in ProductManager
- `GuestMenu.php` cart validation (lines 669-691) — Already catches unavailable items at checkout with `guest.items_unavailable` translation key
- `AuditLog::record()` — Existing audit log pattern for tracking availability changes

### Established Patterns
- Product visibility uses `is_visible` (hide from menu entirely) vs `is_available` (temporarily unavailable) — two separate concepts
- Guest menu queries chain `.where('is_visible', true)->where('is_available', true)` — Phase 3 changes the `is_available` filter to show-but-grey instead of hide
- POS dashboard uses Livewire `$this->dispatch('toast', ...)` for user feedback
- ProductManager uses Livewire 3 forms pattern with `save()` method

### Integration Points
- `ProductManager.php` — Add inline toggle (new wire:click method) and form field
- `GuestMenu.php` `render()` method — Remove `is_available` filter from query, add availability state to template data
- `guest-menu.blade.php` — Add greyed-out CSS classes and badge markup for unavailable items
- `resources/views/livewire/product-manager.blade.php` — Add toggle UI to product list rows and edit form

</code_context>

<specifics>
## Specific Ideas

No specific references — open to standard approaches within the decisions above.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 03-item-availability*
*Context gathered: 2026-03-21*

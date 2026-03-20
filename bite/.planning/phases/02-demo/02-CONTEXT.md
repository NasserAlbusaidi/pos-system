# Phase 2: Demo - Context

**Gathered:** 2026-03-20
**Status:** Ready for planning

<domain>
## Phase Boundary

Create the Sourdough Oman shop in Bite-POS with full 33-item menu, correct branding, and verified end-to-end order flow. This is a DATA phase — no new features, just populating an existing system with real client data for a demo pitch.

</domain>

<decisions>
## Implementation Decisions

### Shop creation
- **D-01:** ~~Create the Sourdough shop manually via the admin panel~~ → **UNLOCKED:** Use a database seeder (`SourdoughMenuSeeder`) to create the shop and all menu items programmatically. Rationale: Snap-to-Menu not built, executor agents can't use admin panel, seeder is automatable and reproducible.
- **D-02:** Brand colors: paper #F5F0E8, accent #C4975A, ink #2C2520 (locked from Phase 1)
- **D-03:** Shop slug: `sourdough` (or similar — for the QR code URL `/menu/sourdough`)

### Menu data entry
- **D-04:** ~~Use Snap-to-Menu AI feature~~ → **UNLOCKED:** Snap-to-Menu does not exist in codebase. Menu items hardcoded in seeder from the Sourdough PDF menu data.
- **D-05:** ~~User will provide the PDF file path~~ → N/A (seeder approach)
- **D-06:** ~~Manual entry via admin panel after extraction~~ → Review seeder output in guest menu after running
- **D-07:** 33 items total across multiple categories with bilingual names (EN/AR) and OMR prices (3 decimal places)

### Photos
- **D-08:** Skip product photos for now — use placeholder icons (fork-knife SVG from Phase 1)
- **D-09:** Real photos will be added later when Sourdough provides them separately
- **D-10:** The PDF has photos embedded but extracting them is deferred

### End-to-end verification
- **D-11:** Manual walkthrough: QR scan (or direct URL) → browse menu → add items to cart → place order → verify order appears on KDS → mark ready
- **D-12:** This is a human verification checkpoint, not an automated test

### Claude's Discretion
- Category structure (how to group the 33 items into categories)
- Any admin panel UX issues discovered during data entry
- KDS verification details

</decisions>

<specifics>
## Specific Ideas

- The pitch strategy is "Pre-Built Sourdough" — walk into their bakery showing their own menu running on Bite-POS
- Key pitch line: "Talabat for your dine-in customers, except you keep 100% and the line moves faster"
- The shop needs to look real enough that the owner sees THEIR bakery, not a generic demo
- Even without photos, the warm branding cascade from Phase 1 should make the menu feel on-brand

</specifics>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Snap-to-Menu feature
- `app/Livewire/SnapToMenu.php` — AI menu extraction component
- `app/Services/SnapToMenuService.php` — Extraction service logic

### Shop provisioning
- `app/Services/ShopProvisioningService.php` — Creates shop + owner user in DB transaction

### Admin panel
- `app/Livewire/ProductManager.php` — Product CRUD (create/edit/delete products)
- `app/Livewire/ManageShop.php` — Shop settings including branding

### Phase 1 outputs (guest menu is ready)
- `resources/views/livewire/guest-menu.blade.php` — Polished guest menu with 2-col grid, accordion, branding
- `resources/views/layouts/app.blade.php` — Branding cascade deriving CSS tokens from 3 colors

### KDS
- `app/Livewire/KitchenDisplay.php` — Kitchen display system for order tracking

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **ShopProvisioningService:** Can create shop + owner in a DB transaction — but user chose manual admin panel entry
- **Snap-to-Menu:** Already built and shipped — extracts menu data from images/PDFs using AI
- **ProductManager:** Full CRUD for products with image upload, bilingual fields, categories, pricing

### Established Patterns
- **Branding JSON:** Shop model stores `{ accent, paper, ink }` in branding column — Phase 1 cascade handles the rest
- **HasTranslations trait:** `name_en`/`name_ar` pairs on Product, Category — `translated('name')` resolves by locale
- **formatPrice helper:** Auto-formats OMR with 3 decimal places

### Integration Points
- **Guest menu URL:** `/menu/{shop:slug}` — the QR code points here
- **KDS:** Orders placed via guest menu appear on KDS automatically (via Livewire events)
- **Order lifecycle:** unpaid → paid → preparing → ready → completed

</code_context>

<deferred>
## Deferred Ideas

- Extract photos from PDF programmatically — do later when Sourdough provides files or extract manually
- ~~Database seeder for Sourdough data~~ → PROMOTED to active (D-01 unlocked)
- QR code generation for the Sourdough shop — out of scope, use direct URL for demo

</deferred>

---

*Phase: 02-demo*
*Context gathered: 2026-03-20*

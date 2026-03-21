# Roadmap: Bite-POS

## Milestones

- ✅ **v1.0 Sourdough Demo** — Phases 1-2 (shipped 2026-03-21)
- 🚧 **v1.1 Customization & Polish** — Phases 3-6 (in progress)

## Phases

<details>
<summary>✅ v1.0 Sourdough Demo (Phases 1-2) — SHIPPED 2026-03-21</summary>

- [x] Phase 1: Polish (3/3 plans) — completed 2026-03-20
- [x] Phase 2: Demo (2/2 plans) — completed 2026-03-21

See: `.planning/milestones/v1.0-ROADMAP.md` for full details

</details>

### 🚧 v1.1 Customization & Polish (In Progress)

**Milestone Goal:** Give shops visual identity and operational control — selectable themes, custom fonts, optimized images, and item availability toggles.

- [ ] **Phase 3: Item Availability** - Admin toggles products sold-out; guest menu shows greyed badge instead of hiding
- [ ] **Phase 4: Image Optimization** - Uploaded images auto-resize and convert to WebP with size variants
- [ ] **Phase 5: Menu Themes** - Three preset themes selectable per shop with brand color overrides
- [ ] **Phase 6: Custom Fonts** - Admin types a Google Font name; system fetches and self-hosts it

## Phase Details

### Phase 3: Item Availability
**Goal**: Operators can mark items sold out in real time and guests see accurate availability on the menu
**Depends on**: Phase 2 (v1.0 complete)
**Requirements**: AVAIL-01, AVAIL-02, AVAIL-03
**Success Criteria** (what must be TRUE):
  1. Admin or manager can toggle a product's availability on/off from the menu builder without leaving the page
  2. Guest menu shows unavailable products with a greyed-out card and a visible "Sold Out" badge — items are not hidden
  3. A guest who added an item to the cart before it was marked sold out sees a clear error at checkout and cannot complete the order with that item
**Plans**: TBD

### Phase 4: Image Optimization
**Goal**: All newly uploaded product images are automatically optimized for fast mobile loading
**Depends on**: Phase 3
**Requirements**: IMG-01, IMG-02, IMG-03, IMG-04
**Success Criteria** (what must be TRUE):
  1. Uploading a product image automatically produces a thumbnail (200px), card (400px), and full (800px) size variant in WebP format with the original preserved
  2. The guest menu loads product images using the card-size WebP variant with lazy loading — not the original upload
  3. A product save does not fail or stall visibly when a photo up to 5MB is uploaded
  4. If the server's GD driver does not support WebP, the system falls back to JPEG without silent failure
**Plans**: TBD

### Phase 5: Menu Themes
**Goal**: Shops can choose a visual identity for their guest menu from three distinct preset themes
**Depends on**: Phase 4
**Requirements**: THEME-01, THEME-02, THEME-03, THEME-04, THEME-05
**Success Criteria** (what must be TRUE):
  1. Shop settings shows a theme picker with visual previews of three distinct themes (warm, modern, dark) and the currently selected theme is highlighted
  2. Selecting a theme and saving immediately changes the guest menu's color palette, font pairing, and layout style
  3. Shop brand colors (paper/ink/accent) apply on top of the selected theme — switching themes does not discard brand colors
  4. All three themes render correctly in Arabic RTL layout with no letter-spacing artifacts or font fallback failures
  5. A live preview of the theme change is visible in settings before the admin commits the save
**Plans**: TBD

### Phase 6: Custom Fonts
**Goal**: Shops can use any Google Font on their guest menu, self-hosted with no external CDN dependency
**Depends on**: Phase 5
**Requirements**: FONT-01, FONT-02, FONT-03, FONT-04, FONT-05
**Success Criteria** (what must be TRUE):
  1. Admin can type a Google Font family name in shop settings and see a preview of that font before saving
  2. After saving, the guest menu loads the font from local storage — no request goes to fonts.googleapis.com or fonts.gstatic.com
  3. When available, the Arabic subset of the chosen font is also fetched and applied in RTL sections of the menu
  4. Typing an invalid, misspelled, or unavailable font name surfaces a clear error message and leaves the current font unchanged
**Plans**: TBD

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Polish | v1.0 | 3/3 | Complete | 2026-03-20 |
| 2. Demo | v1.0 | 2/2 | Complete | 2026-03-21 |
| 3. Item Availability | v1.1 | 0/TBD | Not started | - |
| 4. Image Optimization | v1.1 | 0/TBD | Not started | - |
| 5. Menu Themes | v1.1 | 0/TBD | Not started | - |
| 6. Custom Fonts | v1.1 | 0/TBD | Not started | - |

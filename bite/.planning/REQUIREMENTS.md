# Requirements: Bite-POS — Sourdough Demo

**Defined:** 2026-03-20
**Core Value:** Customers scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line

## v1 Requirements

### Guest Menu Visual

- [ ] **GMVIZ-01**: Guest menu displays product photos with correct `/storage/` URL prefix
- [ ] **GMVIZ-02**: Product photos use `object-contain` to preserve cut-out shapes without cropping
- [ ] **GMVIZ-03**: Product names display in sentence case (not forced uppercase)
- [ ] **GMVIZ-04**: Guest menu uses 2-column compact card grid on all screen sizes
- [ ] **GMVIZ-05**: Compact cards show photo + name + price (description hidden, reveals on interaction)
- [x] **GMVIZ-06**: Category headers use Playfair Display serif font (self-hosted)
- [ ] **GMVIZ-07**: Image containers show skeleton shimmer while photos download
- [ ] **GMVIZ-08**: Broken/missing images hide gracefully via onerror fallback
- [ ] **GMVIZ-09**: Empty categories (zero visible products) are hidden from guest menu
- [ ] **GMVIZ-10**: Product cards have consistent height regardless of image presence (min-height)

### Branding

- [x] **BRND-01**: All CSS tokens (--canvas, --panel, --panel-muted, --line, --ink-soft) derived from 3 brand colors
- [x] **BRND-02**: Background gradient uses derived tokens instead of hardcoded/default values
- [x] **BRND-03**: Card surfaces and borders reflect shop's warm palette end-to-end

### Testing

- [ ] **TEST-01**: Feature test: product with image_url renders `<img>` with `/storage/` prefix
- [ ] **TEST-02**: Feature test: shop with custom branding renders derived CSS variables

### Demo Prep

- [ ] **DEMO-01**: Sourdough shop created with branding colors (paper: #F5F0E8, accent: #C4975A, ink: #2C2520)
- [ ] **DEMO-02**: All 33 menu items entered with bilingual names (EN/AR), prices, and photos
- [ ] **DEMO-03**: End-to-end flow verified: QR scan → browse → add to cart → order → KDS ticket

## v2 Requirements

### Guest Menu Enhancement

- **GMVIZ-V2-01**: Image optimization pipeline (resize, WebP conversion) for faster mobile loading
- **GMVIZ-V2-02**: Per-shop photo style (cover vs contain) configurable in branding
- **GMVIZ-V2-03**: Per-shop name casing configurable in branding
- **GMVIZ-V2-04**: Item availability indicators ("sold out", "limited")
- **GMVIZ-V2-05**: Menu templates/themes (selectable layouts)
- **GMVIZ-V2-06**: Custom font per shop

### Branding Enhancement

- **BRND-V2-01**: Defensive hex-to-RGB fallback for malformed branding values
- **BRND-V2-02**: Shop logo display in guest menu header

## Out of Scope

| Feature | Reason |
|---------|--------|
| Thawani Pay integration | Separate initiative, not needed for demo |
| Snap-to-Menu photo extraction | Photos available from Sourdough PDF |
| 45-minute seating timer notice | Operational feature, not menu display |
| Progressive blur-up image loading | Skeleton shimmer sufficient; blur-up requires thumbnail pipeline |
| Horizontal category carousels | Hides items; vertical 2-col grid better for scanability |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| GMVIZ-01 | Phase 1 | Pending |
| GMVIZ-02 | Phase 1 | Pending |
| GMVIZ-03 | Phase 1 | Pending |
| GMVIZ-04 | Phase 1 | Pending |
| GMVIZ-05 | Phase 1 | Pending |
| GMVIZ-06 | Phase 1 | Complete |
| GMVIZ-07 | Phase 1 | Pending |
| GMVIZ-08 | Phase 1 | Pending |
| GMVIZ-09 | Phase 1 | Pending |
| GMVIZ-10 | Phase 1 | Pending |
| BRND-01 | Phase 1 | Complete |
| BRND-02 | Phase 1 | Complete |
| BRND-03 | Phase 1 | Complete |
| TEST-01 | Phase 1 | Pending |
| TEST-02 | Phase 1 | Pending |
| DEMO-01 | Phase 2 | Pending |
| DEMO-02 | Phase 2 | Pending |
| DEMO-03 | Phase 2 | Pending |

**Coverage:**
- v1 requirements: 18 total
- Mapped to phases: 18
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-20*
*Last updated: 2026-03-20 after roadmap creation*

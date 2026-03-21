# Requirements: Bite-POS v1.1

**Defined:** 2026-03-21
**Core Value:** Customers scan a QR code, browse a beautiful digital menu with photos, and place orders without waiting in line

## v1.1 Requirements

Requirements for v1.1 Customization & Polish. Each maps to roadmap phases.

### Item Availability

- [x] **AVAIL-01**: Admin/manager can toggle a product's availability on/off from the menu builder
- [x] **AVAIL-02**: Guest menu shows unavailable products greyed out with a "Sold Out" badge instead of hiding them
- [x] **AVAIL-03**: Guest cart validates item availability at checkout and surfaces a clear error if a stale item is in the cart

### Image Optimization

- [x] **IMG-01**: Product images are automatically resized on upload (max 800px longest edge)
- [x] **IMG-02**: Product images are automatically converted to WebP on upload, originals deleted after variants confirmed
- [x] **IMG-03**: Multiple image size variants are generated on upload (thumbnail 200px, card 400px, full 800px)
- [ ] **IMG-04**: Guest menu uses optimized image variants with lazy loading

### Menu Themes

- [ ] **THEME-01**: Shop can select from 3 preset themes, each defining layout style, color palette, and font pairing
- [ ] **THEME-02**: Theme picker is available in shop settings with visual preview of each theme
- [ ] **THEME-03**: Shop can override brand colors (paper/ink/accent) on top of the selected theme
- [ ] **THEME-04**: All 3 themes render correctly in RTL (Arabic) layout
- [ ] **THEME-05**: Theme picker shows a live preview before saving

### Custom Fonts

- [ ] **FONT-01**: Admin can enter a Google Font name in shop settings
- [ ] **FONT-02**: System fetches the font WOFF2 file from Google Fonts and self-hosts it in public storage
- [ ] **FONT-03**: Font preview is shown in settings before saving
- [ ] **FONT-04**: Arabic subset is fetched when available for bilingual support
- [ ] **FONT-05**: Invalid or unavailable font names fall back gracefully to the theme default without corrupting settings

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Stock Management

- **STOCK-01**: Admin can set stock count per product, auto-marks sold out at zero
- **STOCK-02**: Stock decrements automatically when orders are placed

### Image Pipeline Advanced

- **IMGADV-01**: On-the-fly image variant generation for non-standard sizes
- **IMGADV-02**: CDN integration for image delivery

## Out of Scope

| Feature | Reason |
|---------|--------|
| Thawani Pay integration | Separate initiative, not part of customization milestone |
| Menu templates marketplace (user-uploaded themes) | Complexity; 3 curated presets sufficient for v1.1 |
| Custom CSS editor per shop | Security risk (XSS), curated themes provide enough control |
| Imagick driver | GD is sufficient for food photo sizes; Imagick adds server dependency |
| Font file upload (non-Google) | Google Fonts covers 99% of use cases; arbitrary file upload adds security surface |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| AVAIL-01 | Phase 3 | Complete |
| AVAIL-02 | Phase 3 | Complete |
| AVAIL-03 | Phase 3 | Complete |
| IMG-01 | Phase 4 | Complete |
| IMG-02 | Phase 4 | Complete |
| IMG-03 | Phase 4 | Complete |
| IMG-04 | Phase 4 | Pending |
| THEME-01 | Phase 5 | Pending |
| THEME-02 | Phase 5 | Pending |
| THEME-03 | Phase 5 | Pending |
| THEME-04 | Phase 5 | Pending |
| THEME-05 | Phase 5 | Pending |
| FONT-01 | Phase 6 | Pending |
| FONT-02 | Phase 6 | Pending |
| FONT-03 | Phase 6 | Pending |
| FONT-04 | Phase 6 | Pending |
| FONT-05 | Phase 6 | Pending |

**Coverage:**
- v1.1 requirements: 17 total
- Mapped to phases: 17
- Unmapped: 0

---
*Requirements defined: 2026-03-21*
*Last updated: 2026-03-21 — traceability complete after roadmap creation*

# Phase 1 UI/UX Polish Design

**Date:** 2026-03-02
**Scope:** Roadmap Tasks #2-15 (all Phase 1 polish items except currency, which is done)

## Groups

### A: Guest Menu Polish (Tasks #2-4)
- **#2 Product images**: Render `image_url` in guest menu product cards
- **#3 Cart controls**: Add +/- quantity and remove buttons in review modal
- **#4 Modifier names**: Show actual modifier names instead of "+ N options"

### B: POS Dashboard Polish (Tasks #5-7)
- **#5 Item preview**: Show order items in POS ticket cards
- **#6 Quick-pay**: Cash/Card buttons directly on unpaid cards
- **#7 Time elapsed**: Relative time with color-coded urgency

### C: KDS Visual Improvements (Task #11)
- Larger fonts, color-coded time bars, order count badge, bump animation

### D: Toast Notifications (Task #8)
- Alpine.js toast component replacing session flash messages

### E: Styled Confirmation Modals (Task #10)
- Reusable confirm modal component replacing browser confirm()

### F: Loading Skeletons (Task #9)
- Shimmer placeholders during Livewire loading states

### G: Shop Dashboard (Tasks #12-13)
- **#12**: Chart.js bar chart for weekly revenue
- **#13**: QR code generation and display for guest menu

### H: Settings Expansion (Tasks #14-15)
- **#14**: Currency config, receipt header, QR download in settings
- **#15**: Staff management CRUD with PIN and role assignment

## Implementation Strategy
All groups are independent. Dispatch parallel agents per group.

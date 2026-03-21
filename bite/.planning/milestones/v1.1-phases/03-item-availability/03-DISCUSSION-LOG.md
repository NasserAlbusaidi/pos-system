# Phase 3: Item Availability - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-21
**Phase:** 03-item-availability
**Areas discussed:** Sold-out display, Admin toggle UX, Cart error recovery

---

## Sold-Out Display

### Tappable behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Tappable, no add | Guests can tap to see details but "Add to cart" is disabled/hidden | |
| Completely locked | Card is greyed + non-interactive — no expand, no details, just badge | |
| You decide | Claude picks the best approach | ✓ |

**User's choice:** You decide
**Notes:** Claude has discretion on whether to allow tapping sold-out items for details

### Badge position

| Option | Description | Selected |
|--------|-------------|----------|
| Over the image | Diagonal ribbon or overlay badge on the product image area | ✓ |
| Replace price | Where the price normally shows, display "Sold Out" instead | |
| Below name | Small badge/tag under the product name, price still visible | |
| You decide | Claude picks the best placement | |

**User's choice:** Over the image

### Grey level

| Option | Description | Selected |
|--------|-------------|----------|
| Subtle fade | ~50% opacity — still readable, clearly different | |
| Heavy fade | ~30% opacity — almost ghost-like | |
| You decide | Claude picks appropriate opacity | ✓ |

**User's choice:** You decide

### Sort order

| Option | Description | Selected |
|--------|-------------|----------|
| Stay in place | Keep original sort order — consistent layout | ✓ |
| Sort to bottom | Push sold-out items below available ones | |
| You decide | Claude picks | |

**User's choice:** Stay in place

---

## Admin Toggle UX

### Toggle placement

| Option | Description | Selected |
|--------|-------------|----------|
| Inline on product row | Toggle switch visible on product list row — one-click | |
| In edit form only | Toggle inside the product edit form | |
| Both places | Quick toggle on row + also in edit form | ✓ |

**User's choice:** Both places

### Terminology

| Option | Description | Selected |
|--------|-------------|----------|
| 86'd language | Match POS: "Mark as 86'd" / "Back on menu" | |
| Simple language | "Available" / "Sold Out" — universal | ✓ |
| You decide | Claude picks | |

**User's choice:** Simple language — "Available" / "Sold Out"

---

## Cart Error Recovery

### Recovery flow

| Option | Description | Selected |
|--------|-------------|----------|
| Modal with options | Show modal listing unavailable items, guest removes and continues | |
| Auto-remove + toast | Auto-remove, show toast, continue checkout | |
| Block + explain | Block checkout, highlight items, require manual removal | |
| You decide | Claude picks the best recovery flow | ✓ |

**User's choice:** You decide
**Notes:** Backend catch already exists in GuestMenu.php; Claude has discretion on frontend UX

---

## Claude's Discretion

- Tappable vs locked greyed-out cards
- Exact opacity for greyed-out treatment
- Cart error recovery UX pattern
- Badge visual design details
- Loading/transition animations

## Deferred Ideas

None — discussion stayed within phase scope.

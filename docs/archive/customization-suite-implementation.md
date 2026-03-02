# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Technical Implementation Plan: Shop Customization Suite

## Track 1: Database & Core Logic (TDD Driven)

### Task 1.1: Recursive Category Structure
*   **Migration:** `alter_categories_add_parent_id` (Nullable foreignId to categories).
*   **Model:** 
    *   `parent()`: BelongsTo
    *   `children()`: HasMany
*   **Verification:** TDD cycle to ensure `Category::find(x)->children` returns nested items.

### Task 1.2: Dynamic Floor Plan Data
*   **Migration:** `create_tables_table` (id, shop_id, label, x, y).
*   **Model:** `Table` (Shop ID scoping).
*   **Verification:** Test persisting and retrieving float coordinates.

### Task 1.3: Item-Level Discount Logic
*   **Migration:** `alter_products_add_discounts` (discount_value, discount_type, is_on_sale).
*   **Logic:** Helper method `Product::getFinalPriceAttribute()` to return calculated price if on sale.

---

## Track 2: Builder Interfaces (Experience Track)

### Task 2.1: The Menu Builder (Drag & Drop)
*   **Component:** `App\Livewire\Admin\MenuBuilder`
*   **Library:** SortableJS integration.
*   **Workflow:**
    *   Left Panel: Categories (Folders).
    *   Main Canvas: Product Tiles.
    *   Action: Drag item to category -> Update `category_id` via Livewire `dispatch`.

### Task 2.2: The Floor Planner (Snap-to-Grid)
*   **Component:** `App\Livewire\Admin\FloorPlanner`
*   **Library:** interact.js
*   **Workflow:**
    *   Grid Overlay (20px subdivisions).
    *   `onDragEnd`: Calculate nearest grid intersection, convert to percentage, save to DB.

---

## Track 3: Guest Experience Overhaul

### Task 3.1: Dynamic CSS Injection
*   **Logic:** `AppServiceProvider` or Layout helper to fetch `Shop::branding`.
*   **Implementation:** Inline `<style>` block in `layouts.app` using CSS variables:
    `--primary-color: {{ $shop->primary_color }};`

### Task 3.2: Real-time Order Tracking
*   **Route:** `/track/{order:id}`
*   **Component:** `App\Livewire\Guest\OrderTracker`
*   **Features:**
    *   Visual "Steps" (Ordered -> Prep -> Ready).
    *   5s `wire:poll` for status updates.
    *   Table number confirmation.

---

## Task 3.3: Pre-Flight Cart Review
*   **Component:** Update `GuestMenu` to include a full-screen "Review" state.
*   **Features:** Line-item detail, Modifier breakdown, Table number assignment.

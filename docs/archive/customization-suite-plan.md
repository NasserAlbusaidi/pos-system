# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Architectural Plan: Shop Customization Suite

## 1. Core Data Architecture
To support deep customization, the database schema will be extended to handle spatial data, recursive structures, and dynamic pricing.

### Extension Points:
*   **Recursive Categories:** Modify `categories` table to include `parent_id` (Self-referencing foreign key). Logic will support infinite nesting, though UI will likely optimize for 3 levels.
*   **Floor Plan System:** Create a new `tables` table:
    *   `id`, `shop_id`, `label` (e.g., "Table 4").
    *   `x`, `y` (Float): Percentage-based coordinates (0-100) to ensure the map renders correctly on all screen sizes.
*   **Discount Engine (Item Level):** Add fields to `products`:
    *   `discount_value` (Decimal): The amount or percentage.
    *   `discount_type` (Enum): `fixed` or `percentage`.
    *   `sale_active` (Boolean): Master toggle for the discount.
*   **Branding Payload:** Add `branding` (JSON) column to `shops` table:
    *   Keys: `primary_color`, `secondary_color`, `font_family`, `logo_path`, `header_style`.
*   **Visibility Control:** `is_visible` (Boolean) on both `products` and `categories` to allow "Draft" or "Out of Stock" states.

## 2. Visual Menu Builder
The Menu Builder will be an interactive "Canvas" where owners can orchestrate their offerings with zero latency.

### Key Components:
*   **Drag & Drop Workspace:** Categories and Products are rendered as draggable tiles. Moving a product between category containers automatically updates the `category_id` via Livewire.
*   **Intelligent Search:** A persistent search bar allows owners to instantly locate products across all categories for quick price adjustments or visibility toggles.
*   **Media Center:** A drag-drop image upload zone for every product. Images will be automatically optimized and served via GCS.
*   **Inline Modification:** Clicking a price or name activates an input field for instant updates, utilizing Livewire's "debounced" sync to save changes without a "Save" button.
*   **Sale Manager:** A dedicated "Flash Sale" toggle on product tiles to apply and showcase discounts immediately.

## 3. Dynamic Floor Planner
The Floor Planner transforms shop management into a visual layout experience, ensuring staff and guests have a clear spatial reference.

### Mechanics:
*   **Grid System:** A CSS-based grid background (e.g., 20px subdivisions). 
*   **Snap-to-Grid:** Using Alpine.js and `interact.js` (or similar), table components will "snap" to the nearest grid intersection to maintain a professional, aligned appearance.
*   **Persistent Coordinates:** Every table's position is stored as a percentage (`x: 45.5%, y: 20.0%`) to ensure the layout remains identical regardless of the screen size (Tablet vs. Desktop).
*   **State Management:**
    *   **Edit Mode:** Full drag-and-drop capability and deletion.
    *   **Live Mode:** Tables are locked; clicking a table opens the "Table Session" (Orders/Bill).

## 4. Guest Experience & Branding
The Guest Menu evolves into a tailor-made experience for each shop, increasing customer trust and sales.

### Customer Journey:
*   **Themed Interface:** The UI dynamically loads the shop's `branding` payload (Colors, Fonts) from the database and injects them into the CSS variables of the page.
*   **Sale Highlighting:** Items with `sale_active` will display a "SALE" badge and a strike-through price (e.g., ~~$5.00~~ **$4.00**).
*   **The Pre-Flight Cart:** A mandatory "Review Order" screen.
    *   Lists all items and selected modifiers.
    *   Displays **Table Number** metadata.
    *   Total calculation with applied discounts.
*   **Transmission Tracking:** Upon order submission, the guest is redirected to a unique `/track/{id}` endpoint.
    *   **Live Status:** Visual progress bar (ORDERED -> PREPARING -> READY).
    *   **Table Verification:** Confirms the order is being sent to their specific table.
    *   **Polling Refresh:** Component polls every 5s to update status instantly.

## 5. Implementation Roadmap

### Sprint 1: Foundation (Logic Track)
*   **Database:** Implement recursive categories, table coordinates, and discount fields.
*   **Logic:** Update models to handle `parent_id` relationships and discount calculations.
*   **Media:** Setup Storage disk for image uploads.

### Sprint 2: The Builder (Experience Track)
*   **Menu Builder:** Create the drag-and-drop "Canvas" for categories and products.
*   **Floor Planner:** Implement the grid-based table positioning tool with "Snap-to-Grid".
*   **Real-time:** Ensure drag actions persist to the DB instantly via Livewire.

### Sprint 3: The Custom Journey (Experience Track)
*   **Dynamic Theme:** Implement the CSS variable injector based on shop branding.
*   **Order Detail View:** Build the "Pre-Flight Cart" for guest verification.
*   **Tracking Engine:** Create the `/track/{id}` page with polling status updates.

# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Technical Specification: Bite POS

## 1. Database Schema

### Core Tenancy & Users
*   **shops**: `id`, `name`, `slug` (subdomain), `logo_url`, `settings` (JSON), `created_at`.
*   **users**: `id`, `shop_id`, `name`, `email`, `password`, `role` (admin, manager, server, kitchen), `pin_code` (for quick POS access).

### Menu Management
*   **categories**: `id`, `shop_id`, `name`, `sort_order`, `is_active`.
*   **products**: `id`, `shop_id`, `category_id`, `name`, `description`, `price` (decimal), `image_url`, `is_available`.
*   **modifier_groups**: `id`, `shop_id`, `name` (e.g., "Milk Options"), `min_selection` (0=optional, 1=required), `max_selection`.
*   **modifier_options**: `id`, `modifier_group_id`, `name` (e.g., "Oat Milk"), `price_adjustment` (decimal).
*   **product_modifier_group** (Pivot): `product_id`, `modifier_group_id`.

### Orders & Transactions
*   **orders**: `id`, `shop_id`, `table_number` (nullable), `customer_name` (nullable), `status` (unpaid, paid, preparing, ready, completed, cancelled), `total_amount` (decimal), `payment_method` (cash, card), `paid_at` (datetime), `expires_at` (datetime).
*   **order_items**: `id`, `order_id`, `product_id`, `product_name_snapshot`, `price_snapshot` (decimal), `quantity`.
*   **order_item_modifiers**: `id`, `order_item_id`, `modifier_option_name_snapshot`, `price_adjustment_snapshot`.

*Note: All tables (except `shops`) typically include a `shop_id` foreign key for efficient single-database tenancy.*

## 2. Key Livewire Components & Actions

### Public Facing
*   **GuestMenu (`/menu/{table}`)**
    *   *State:* `cart` (Array), `selectedCategory`.
    *   *Actions:*
        *   `addToCart(productId, modifiers)`: Validates required modifiers and adds to local session state.
        *   `submitOrder()`: Persists data to `orders` table with status `unpaid`. Sets `expires_at` to +6 mins. Triggers "Order Placed" view.

### Staff Facing (POS)
*   **PosDashboard (`/pos`)**
    *   *State:* `activeOrders` (Collection), `selectedOrder`.
    *   *Polling:* Polls `orders` table every 5s for new `unpaid` entries.
    *   *Actions:*
        *   `loadOrder(id)`: Opens the bill details.
        *   `processPayment(orderId, method)`: Updates status to `paid`, sets `paid_at`. **Critical:** Triggers event to update KDS.
        *   `splitBill(orderId, strategy)`: detailed logic for separating line items into new sub-orders.
        *   `voidOrder(id)`: Cancels ghost orders manually.

### Kitchen Operations
*   **KitchenDisplay (`/kds`)**
    *   *State:* `ticketQueue`.
    *   *Polling:* Polls for orders where `status` IN (`paid`, `preparing`).
    *   *Actions:*
        *   `advanceStatus(orderId)`: Moves from `paid` -> `preparing` -> `ready`.

### Administration
*   **ProductManager**
    *   *Actions:* CRUD operations for products and modifier assignment.

## 3. Roles & Permissions

*   **Owner (Admin):** Full access to all modules, including Shop Settings, Billing, and User Management.
*   **Manager:** Access to Menu Management, Sales Reports, and Inventory. Cannot delete the Shop or change billing.
*   **Server:** Access to POS Dashboard. Permission to create/edit orders and process payments. Requires Manager PIN for `voidOrder` or high-value refunds.
*   **Kitchen:** Restricted access solely to the KDS view (`/kds`).

## 4. Sprint 1 Plan (Foundations & Menu)

**Goal:** Establish the architecture and achieve "Hello World" (Database -> Admin Input -> Guest View).

*   **Task 1.1: Project Setup**
    *   Initialize Laravel 11 project.
    *   Install Livewire, Tailwind (TALL preset).
    *   Configure `stancl/tenancy` or custom Middleware for `shop_id` scoping.
*   **Task 1.2: Database Implementation**
    *   Create migrations for `shops`, `users`, `products`, `categories`.
    *   Seed database with one demo shop ("Bite Demo Coffee").
*   **Task 1.3: Admin Menu MVP**
    *   Build `ProductManager` Livewire component (Create/Edit Product).
*   **Task 1.4: Guest Menu Read-Only**
    *   Build `GuestMenu` component to list products by category.
    *   Verify mobile responsiveness.

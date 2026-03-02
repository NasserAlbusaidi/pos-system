# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Implementation Roadmap: Missing Features

This document outlines the step-by-step plan to implement the missing features identified in the `bite-admin-spec.md` and `customization-suite-plan.md`.

## Phase 1: Super Admin & Shop Management (The "Control Tower")
**Objective:** Enable platform owners to manage tenants, access "God Mode" (impersonation), and oversee system health.

### 1.1. Core Admin Architecture
- [x] **Middleware:** Verify `EnsureUserIsSuperAdmin` middleware is active and registered in `bootstrap/app.php`.
- [x] **Route Group:** Confirm all `/admin/*` routes are protected by this middleware.

### 1.2. Shop Management CRUD
- [x] **Component:** Create `App\Livewire\SuperAdmin\Shops\Index`
    - [x] Searchable table of shops.
    - [x] Status indicators (Active/Suspended).
    - [x] "Login as Owner" button.
- [x] **Component:** Create `App\Livewire\SuperAdmin\Shops\Manage` (Create/Edit)
    - [x] Form for Name, Slug, Owner Email.
    - [x] Manual "Status" toggle (Active/Suspended/Trial).

### 1.3. Impersonation Logic ("God Mode")
- [x] **Controller:** Create `App\Http\Controllers\ImpersonationController`.
    - [x] `impersonate(int $userId)`: Stores original ID in session, logs in as target.
    - [x] `leave()`: Restores original ID.
- [x] **Middleware:** Update global middleware to check `session('impersonator_id')` and inject a global banner ("You are impersonating...").
- [x] **Audit:** Log all impersonation entry/exit events to `system_audits` (create table if missing).

## Phase 2: Visual Customization Suite (The "Builder")
**Objective:** Transform the basic data-entry forms into interactive, visual tools for shop owners.

### 2.1. Drag & Drop Menu Builder
- [x] **Library:** Install `sortablejs` via npm.
- [x] **View Integration (`admin.menu-builder`):**
    - [x] Attach Sortable to the Category list and Product lists.
    - [x] **Event Handling:** On "drop", trigger Livewire `updateOrder($items)` to persist new `sort_order`.
    - [x] **Visual Feedback:** Add "grab" cursors and ghost classes for smooth UX.

### 2.2. Interactive Floor Planner
- [x] **Library:** Use Alpine.js for drag logic (lightweight).
- [x] **Canvas Area:** Create a fixed aspect-ratio container with a grid background (CSS `background-image`).
- [x] **Table Component:**
    - [x] Make table divs draggable within the parent container.
    - [x] **Snap-to-Grid:** Round `x,y` percentages to nearest 5%.
    - [x] **Save:** On `mouseup`, emit `updateTablePosition` to Livewire.

## Phase 3: Dynamic Guest Experience
**Objective:** Ensure the guest interface actually looks like the specific shop, not a generic template.

### 3.1. Dynamic Branding Engine
- [x] **CSS Variable Injection:**
    - [x] Update `resources/views/layouts/app.blade.php` (or Guest Layout).
    - [x] Add a `<style>` block in the `<head>`.
    - [x] Loop through `Auth::user()->shop->branding` (or `$shop->branding`) and set `:root` variables:
        ```css
        :root {
            --color-paper: {{ $shop->branding['paper'] ?? '#f5f5f5' }};
            --color-ink: {{ $shop->branding['ink'] ?? '#1a1a1a' }};
            --color-crema: {{ $shop->branding['accent'] ?? '#ff4d00' }};
        }
        ```
- [x] **Fallback Defaults:** Ensure standard Bite colors are used if branding is null.

### 3.2. Order Tracking Polish
- [x] **Polling:** Ensure `OrderTracker` component polls status every 5s.
- [x] **Visuals:** Add a progress bar (Ordered -> Preparing -> Ready) that fills based on `order.status`.

## Execution Order
1.  **Phase 1** (Security & Admin) is the priority to ensure we can actually manage the tenants we are creating.
2.  **Phase 3.1** (Branding) is a quick win to make the demos look better.
3.  **Phase 2** (Builders) is the complex UI work for the end-user dashboard.

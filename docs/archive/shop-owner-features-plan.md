# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Implementation Plan: Shop Owner Features

## Phase 1: Build
- [ ] **Feature: Shop Dashboard**
    - [ ] Update `ShopDashboard` logic to filter `activeTablesCount` correctly.
    - [ ] Ensure `recentOrders` displays correctly.
- [ ] **Feature: Shop Settings**
    - [ ] Update `ShopSettings` to use correct keys for branding (`paper`, `ink`, `accent`) to match the `layouts/app.blade.php` implementation.
    - [ ] Fix color validation regex.
- [ ] **Feature: Product Manager**
    - [ ] Review `ProductManager` implementation (verify existence and logic).
- [ ] **Feature: Modifier Manager**
    - [ ] Review `ModifierManager` implementation (verify existence and logic).

## Phase 2: Verify
- [ ] Run tests for Shop Dashboard.
- [ ] Run tests for Shop Settings.
- [ ] Run tests for Product Manager.
- [ ] Run tests for Modifier Manager.

## Phase 3: Document
- [ ] Update `DEV_LOG.md` with changes.

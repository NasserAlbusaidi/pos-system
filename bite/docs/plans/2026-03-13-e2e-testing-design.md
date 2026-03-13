# E2E Testing Design — Laravel Dusk

**Date:** 2026-03-13
**Status:** Approved
**Framework:** Laravel Dusk
**Database:** Dedicated MySQL (`bite_testing`)

## Infrastructure

### Environment

- `.env.dusk.local` with `DB_DATABASE=bite_testing`, `APP_URL=http://127.0.0.1:8000`
- `php artisan migrate:fresh --seed --env=dusk.local` before suite
- ChromeDriver via Dusk's built-in installer

### CI Integration

Add a Dusk job to the existing GitHub Actions workflow with a MySQL service container.

### Directory Structure

```
tests/Browser/
├── Pages/              # Dusk Page objects
│   ├── PosPage.php
│   ├── GuestMenuPage.php
│   ├── KdsPage.php
│   ├── LoginPage.php
│   ├── PinLoginPage.php
│   ├── DashboardPage.php
│   ├── MenuBuilderPage.php
│   ├── SettingsPage.php
│   └── SuperAdminPage.php
├── Phase1/             # Money flows
├── Phase2/             # Access & setup
├── Phase3/             # Admin & billing
└── DuskTestCase.php    # Base class with shared helpers
```

## Phase 1 — Money Flows (7 tests, ~25 assertions)

### PosOrderTest
- Admin logs in, navigates to POS
- Selects category, adds product to cart
- Adds modifier to product
- Verifies cart total (OMR 3 decimals)
- Clicks pay, confirms payment
- Asserts order created with status `paid`
- Asserts order appears on KDS

### PosModifierTest
- Adds product with required modifier group — must select before adding
- Adds product with optional modifier — skips it
- Verifies modifier prices reflected in cart total

### PosSplitOrderTest
- Creates order with multiple items
- Splits order — verifies two orders with correct `split_group_id`
- Both orders have correct totals

### GuestOrderFlowTest
- Visits `/menu/{slug}` (no auth)
- Browses categories, selects product
- Adds modifiers, adds to cart
- Places order, redirected to tracking page
- Asserts tracking page shows order status
- Asserts order exists in DB with `tracking_token`

### GuestOrderTrackingTest
- Creates order via factory with tracking token
- Visits `/track/{token}`
- Asserts order details visible
- Transitions order status in DB, refreshes, asserts new status

### KdsLifecycleTest
- Kitchen user logs in, navigates to KDS
- Asserts paid order appears
- Transitions: `paid -> preparing -> ready -> completed`
- Each status change reflects on screen
- Completed order disappears from active view

### KdsMultiOrderTest
- Seeds 3 orders at different statuses
- Asserts correct orders in correct sections
- Transitions one, verifies only that order moved

## Phase 2 — Access & Setup (7 tests, ~28 assertions)

### AuthLoginTest
- Valid credentials -> lands on dashboard
- Invalid credentials -> error message
- Logout -> redirected to login

### PinLoginTest
- Visits `/pos/pin/{slug}`
- Valid 4-digit PIN -> lands on POS
- Wrong PIN -> error
- Correct user identity after PIN login

### RbacAccessTest
- Server: POS yes, settings no
- Kitchen: KDS yes, POS no
- Manager: POS, KDS, reports, settings yes
- Admin: everything including billing
- Unauthenticated: redirected to login

### OnboardingWizardTest
- New admin with no shop -> sees onboarding
- Steps through wizard: shop name, slug, branding
- Completes -> shop created with 14-day trial
- Redirected to dashboard

### MenuBuilderTest
- Creates category (en + ar names)
- Creates product under category with price
- Toggles visibility off -> not on guest menu
- Toggles on -> visible on guest menu

### ProductManagerTest
- Creates product with image, description, price
- Edits name and price
- Adds discount -> discount price shown
- Deletes product -> gone from list

### ModifierManagerTest
- Creates modifier group (required, min 1, max 1)
- Adds options with prices
- Modifier group appears on product in POS
- Edits option name -> change reflected

## Phase 3 — Admin & Billing (9 tests, ~32 assertions)

### ShopDashboardTest
- Dashboard loads, revenue chart renders (Canvas present)
- Key metrics visible (today's orders, revenue)
- QR code displayed

### ReportsDashboardTest
- Selects date range -> data loads
- Exports CSV -> download triggers
- Report scoped to current shop (tenant isolation)

### ShiftReportTest
- Shift data visible
- Order count and revenue match seeded data

### AuditLogsTest
- Performs action (create product)
- Action logged with correct user and timestamp

### ShopSettingsTest
- Updates shop name -> persisted
- Updates branding colors -> CSS custom properties change
- Updates slug -> guest menu at new slug

### BillingSettingsTest
- Current plan displayed (Free/Pro)
- Trial days shown if on trial
- UI renders correctly for each plan state (mocked via factory, no Stripe)

### SuperAdminDashboardTest
- Super admin sees platform stats
- Non-super-admin gets 403

### SuperAdminShopManagementTest
- Views shop list
- Creates shop -> appears in list
- Edits shop -> changes saved

### SuperAdminImpersonationTest
- Impersonates shop admin -> lands on their dashboard
- Impersonation banner visible
- Sees impersonated user's shop data only
- Leaves impersonation -> back to super admin

## Summary

| Phase | Files | Focus | Assertions |
|-------|-------|-------|------------|
| 1 | 7 | Money flows | ~25 |
| 2 | 7 | Access & setup | ~28 |
| 3 | 9 | Admin & billing | ~32 |
| **Total** | **23** | | **~85** |

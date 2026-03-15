# System Review Remaining Fixes

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Resolve all remaining security, performance, and architecture issues from the 16 March system review.

**Architecture:** Small, targeted fixes across 6 files. No schema changes. All fixes are independent and can be parallelized.

**Tech Stack:** Laravel 12, Livewire 3, PHP 8.4

---

### Task 1: TrustProxies for Cloud Run

**Files:**
- Modify: `bootstrap/app.php`

**What:** Cloud Run sits behind a Google load balancer. Without TrustProxies, `request()->ip()` returns the proxy IP — defeating rate limiting on PIN login, registration, and order submission.

**Fix:** Configure Laravel to trust the Cloud Run proxy. In Laravel 11+, use `$middleware->trustProxies()` in bootstrap/app.php.

```php
$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR);
```

**Test:** Existing rate-limit tests still pass. Manual: `request()->ip()` returns client IP, not proxy.

---

### Task 2: Split Order Race Condition

**Files:**
- Modify: `app/Livewire/PosDashboard.php:389-510`

**What:** `applySplit()` fetches the order without `lockForUpdate()`. Concurrent splits could duplicate or lose items.

**Fix:** Wrap the entire split in `DB::transaction()` with `lockForUpdate()` on the order fetch.

**Test:** Existing tests pass. The `markAsDelivered()` method at line 321 already uses this pattern — follow the same approach.

---

### Task 3: Configurable Order Expiry

**Files:**
- Modify: `config/billing.php` — add `order_expiry_minutes` key
- Modify: `app/Livewire/GuestMenu.php:326` — use config value
- Modify: `routes/console.php` — verify cancelExpired uses same value

**What:** Guest order expiry is hardcoded to 6 minutes. Should be configurable.

**Fix:** Add `'order_expiry_minutes' => env('ORDER_EXPIRY_MINUTES', 6)` to billing config. Use `config('billing.order_expiry_minutes')` in GuestMenu.

---

### Task 4: ShopDashboard Eager Loads

**Files:**
- Modify: `app/Livewire/ShopDashboard.php` — render() method

**What:** `recentOrders` query has no eager loading. If blade accesses items/payments, N+1.

**Fix:** Add `->with('items')` to the recentOrders query.

---

### Task 5: Role Authorization in Staff Creation

**Files:**
- Modify: `app/Livewire/ShopSettings.php:163-198`

**What:** Any manager can assign the "manager" role to new staff. Only admins should be able to create managers.

**Fix:** If `Auth::user()->role !== 'admin'`, restrict staffRole to `['cashier', 'kitchen', 'server']` (exclude 'manager').

---

### Task 6: Type Hints on PosDashboard Public Methods

**Files:**
- Modify: `app/Livewire/PosDashboard.php`

**What:** Public Livewire methods accept untyped `$orderId` parameters. Should be `int`.

**Methods:** `markAsPaid`, `markAsDelivered`, `openSplit`, `openPayment`

---

### Execution Order

Tasks 1-6 are fully independent. Execute in parallel where possible. Commit each fix separately. Push and update Notion after all fixes land.

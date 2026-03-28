---
status: resolved
trigger: "Free-plan shops can only access Dashboard & Guest menu. Kitchen Display, Catalog, & Settings should work under the free plan but are blocked."
created: 2026-03-26T00:00:00Z
updated: 2026-03-26T00:00:00Z
---

## Current Focus
<!-- OVERWRITE on each update - reflects NOW -->

hypothesis: CONFIRMED. The `subscribed` middleware alias maps to CheckSubscription, which calls BillingService::isSubscribed(). That method returns true only if shop->subscribed('default') (i.e. has a Stripe subscription record) OR onGenericTrial(). A free-plan shop has neither — no Stripe subscription AND no trial — so isSubscribed() returns false and they get redirected to billing. KDS (/kds) and all admin routes (/products, /settings, etc.) use the `subscribed` middleware. Dashboard (/dashboard) and billing (/billing) do NOT use it, which is why only those work.
test: N/A — root cause confirmed from code reading
expecting: N/A
next_action: Fix CheckSubscription to allow free-plan shops through; OR remove `subscribed` middleware from routes that should be accessible on free plan

## Symptoms
<!-- Written during gathering, then IMMUTABLE -->

expected: Free-plan shops should access Dashboard, Guest Menu, Kitchen Display, Catalog, and Settings. Only POS (server role features) and advanced features should be gated behind Pro.
actual: A test shop created under tester1@bite.com with Active status on the free plan can only access Dashboard & Guest menu. Kitchen Display, Catalog, & Settings are blocked.
errors: No specific error — likely redirected to billing page by CheckSubscription middleware
reproduction: Create a new shop with free plan (no trial), try to access Kitchen Display, Catalog, or Settings
started: Reported 25 Mar 2026 by tester Anas

## Eliminated
<!-- APPEND only - prevents re-investigating -->

- hypothesis: Role middleware is blocking access
  evidence: Routes like /kds use 'role:kitchen,manager,admin' which the admin user would pass. The block happens at the `subscribed` middleware before role is even checked.
  timestamp: 2026-03-26

- hypothesis: BillingService::canAccess() is blocking
  evidence: canAccess() is only called from application code when checking feature limits (add_staff, add_product, reports). It is NOT called by CheckSubscription or any middleware. The middleware only calls isSubscribed().
  timestamp: 2026-03-26

## Evidence
<!-- APPEND only - facts discovered -->

- timestamp: 2026-03-26
  checked: routes/web.php middleware groupings
  found: Three route groups use `subscribed` middleware: (1) POS/invoices [server,manager,admin], (2) KDS [kitchen,manager,admin], (3) all admin routes including /products, /settings, /reports [manager,admin]. Dashboard and billing do NOT use `subscribed`.
  implication: Any free-plan shop (no Stripe subscription, no trial) is redirected to billing when accessing KDS, products, or settings.

- timestamp: 2026-03-26
  checked: BillingService::isSubscribed()
  found: Returns `$shop->subscribed('default') || $shop->onGenericTrial()`. A new shop on free plan (trial expired or never started) has neither condition true.
  implication: Free-plan shops are treated identically to expired-subscription shops — both get blocked.

- timestamp: 2026-03-26
  checked: config/billing.php free plan features
  found: Free plan lists 'POS Terminal', 'Guest Menu', 'Kitchen Display' as features. No Catalog/Settings in the feature list, but the expected behavior (from issue) is that these should be accessible.
  implication: The intent is clear: free plan is a real tier, not "no plan". The system just doesn't distinguish between "free plan" and "no subscription at all".

- timestamp: 2026-03-26
  checked: BillingService::isSubscribed() vs getCurrentPlan()
  found: getCurrentPlan() correctly returns 'free' when there is no subscription. isSubscribed() does NOT account for this — it has no concept of "free plan is still a valid state".
  implication: The fix must make isSubscribed() return true for free-plan shops, OR the middleware must treat free-plan shops as allowed.

## Resolution
<!-- OVERWRITE as understanding evolves -->

root_cause: BillingService::isSubscribed() returns false for free-plan shops (no Stripe subscription record, no generic trial). The `subscribed` middleware uses isSubscribed() as its sole gate. Since KDS, Catalog, and Settings routes all use `subscribed` middleware, free-plan shops get redirected to billing. The free plan was implemented as a Stripe-free tier in config but isSubscribed() was never updated to recognize it as a valid "subscribed" state.
fix: Updated BillingService::isSubscribed() to return true when `$shop->subscription('default')` is null (no subscription record = free plan). Added null-subscription check as third condition after existing Stripe subscription and generic trial checks. Added regression test class FreePlanAccessTest with 8 tests covering all blocked routes + confirmed the expired-subscription redirect still works.
verification: All 8 new regression tests pass. Full suite: 193 pass, 3 fail (pre-existing failures in MenuExtractionServiceTest/OnboardingSnapMenuTest unrelated to this fix).
files_changed: [app/Services/BillingService.php, tests/Feature/FreePlanAccessTest.php]

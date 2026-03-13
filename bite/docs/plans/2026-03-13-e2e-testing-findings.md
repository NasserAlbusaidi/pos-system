# E2E Testing — Findings & Suggestions

**Date:** 2026-03-13
**Scope:** Full Dusk test suite (Phases 1–3), 23 test files, 53 test methods

---

## Current State

| Metric | Value |
|--------|-------|
| Test files | 23 (7 Phase 1, 7 Phase 2, 9 Phase 3) |
| Test methods | 53 |
| Assertions | 99+ |
| Runtime | ~51s full suite |
| Passing | 40 |
| Failing | 13 (all in Phase 2 except 1 Phase 3) |
| Page Objects created | 14 |
| Page Objects used | 0 |
| Lines of test code | 2,024 |

---

## Finding 1: Phase 2 Is Broken (13 of 13 Failures)

12 of 13 failing tests are in Phase 2. The failures break down into three categories:

| Error Type | Tests Affected | Root Cause |
|------------|---------------|------------|
| `InvalidArgumentException` | AuthLoginTest (2), PinLoginTest (3), ProductManagerTest (2) | Selector or argument issues — likely stale selectors after recent UI changes |
| `TimeoutException` | MenuBuilderTest (2), ModifierManagerTest (1) | Waiting for text/elements that don't appear — UI structure changed |
| `ElementNotInteractableException` | AuthLoginTest (1) | Element exists but can't be clicked — likely hidden or overlapped |
| `assertSee` failure | OnboardingWizardTest (1) | Asserts "Currency" text on Step 2, but the onboarding wizard UI may have changed |
| Scope isolation | ReportsDashboardTest (1, Phase 3) | Reports scoped-to-shop test failing — possible data leakage between tests |

**Suggestion:** Phase 2 tests were written against assumed UI structures that don't match the actual pages. These need a targeted fix pass — run each one individually, screenshot on failure, and correct the selectors. The Phase 1 approach of pre-researching the actual UI before writing tests should have been applied to Phase 2 as well.

---

## Finding 2: Page Objects Were Created But Never Used

14 Page Objects were created in Task 2 (`tests/Browser/Pages/`) with defined selectors for every major page. Not a single one is imported or referenced in any of the 23 test files.

All tests use raw inline selectors like:
```php
->click('button[wire\\:click="updateStatus(' . $order->id . ', \'preparing\')"]')
->waitFor('[wire\\:model\\.live="search"]')
```

This means:
- **430+ lines of Page Object code are dead weight** — they exist but do nothing
- **Selectors are duplicated** across tests — the same `wire:click` patterns appear in multiple files
- **UI changes require shotgun surgery** — a renamed Livewire method means finding and fixing every test that references it

**Suggestion:** Either delete the Page Objects entirely (they're unused) or refactor tests to actually use them. Given that most selectors are dynamic (they include record IDs), Page Objects in their current form aren't useful. A better approach: add `dusk="start-preparing"` blade attributes to key interactive elements, then reference those stable selectors in tests instead of brittle `wire:click` selectors.

---

## Finding 3: CSS `uppercase` Was the #1 Plan Deviation

Nearly every test had to adjust for CSS `text-transform: uppercase`. The plan consistently used title-case or sentence-case text in assertions, but `innerText` in the browser returns the CSS-transformed version.

Affected assertions across the suite:
- Product names: "Coffee" → "COFFEE", "Burger" → "BURGER"
- Modifier names: "Size" → "SIZE", "Large" → "LARGE"
- Status labels: "Paid" → "PAID"
- Error messages: "Select at least one item" → "SELECT AT LEAST ONE ITEM TO SPLIT"
- Button labels: "Cash" → "CASH"

**Suggestion:** This is a systematic gap in the planning phase. For future Dusk test planning, always inspect the actual rendered DOM (not Blade source) before writing assertion text. Consider adding a note to the project CLAUDE.md that Dusk `assertSee`/`waitForText` matches CSS-transformed text, not source text.

---

## Finding 4: Plan Assumed Wrong UI Capabilities

Several tests needed significant rewrites because the plan assumed features that don't exist on certain pages:

| Planned | Reality | Impact |
|---------|---------|--------|
| POS has `addToCart` for ordering | POS only manages existing orders (pay/split). Orders are created via Guest Menu | Tasks 4-6 fully rewritten |
| Tracking page shows order items | Tracking page only shows totals + status timeline | Task 8 rewritten |
| Modifiers are on POS | Modifiers are on Guest Menu | Task 5 rewritten |
| `submitOrder()` uses standard redirect | Uses Livewire `navigate: true` (SPA-style) | Task 7 required longer timeouts |

**Suggestion:** For any future test plan, mandate a "UI audit" step before writing test code — read the relevant Blade view and Livewire component to confirm what's actually rendered and what interactions are available. The plan was written from the design doc, not from the actual implementation.

---

## Finding 5: No Real-Time Update Testing

Tests that verify status changes (order tracking, KDS) use manual DB updates + browser refresh:

```php
$order->update(['status' => 'preparing']);
$browser->refresh();
->waitForText('Kitchen is actively preparing');
```

The tracking page has `wire:poll.5s` for live updates, and the KDS has real-time Livewire polling. None of this is actually tested — every test bypasses real-time by doing `$browser->refresh()`.

**Suggestion:** Add at least one test per polling page that waits for the poll interval (e.g., `pause(6000)`) instead of refreshing, to verify the actual real-time behavior users experience.

---

## Finding 6: No Localization Testing

The `SeedsTestData` trait properly seeds both `_en` and `_ar` names for all products/categories. The guest menu supports Arabic via a language switcher. Yet zero tests assert Arabic text or test the locale toggle.

**Suggestion:** Add 1-2 tests in Phase 1 (Guest Order Flow) that switch to Arabic locale and verify `name_ar` fields render correctly. This is a core feature for Omani users.

---

## Finding 7: Inconsistent Test Setup Patterns

Most tests use the `SeedsTestData` trait, but some create factories directly:

- `BillingSettingsTest` — creates Shop/User manually instead of using `createShopWithAdmin()`
- `OnboardingWizardTest` — creates factories directly with `uniqid()` slugs
- `KdsMultiOrderTest` — uses `Product::factory()->create()` for the second product instead of `createProductWithCategory()`

**Suggestion:** Standardize all tests to use `SeedsTestData` helpers. Add any missing helpers (e.g., a `createShopWithAdminAndTrial()` variant if billing tests need trial state). Consistency reduces debugging time when tests break.

---

## Finding 8: Fragile Livewire Selectors

Tests use exact `wire:click` attribute selectors with embedded IDs:

```php
'button[wire\\:click="updateStatus(' . $order->id . ', \'preparing\')"]'
'button[wire\\:click="markAsPaid(' . $order->id . ', \'cash\')"]'
```

These break if:
- The Livewire method is renamed
- Parameter order changes
- A parameter is added/removed
- The method signature changes from positional to named

**Suggestion:** Add `dusk` attributes to Blade templates for key interactive elements:
```html
<button dusk="start-preparing-{{ $order->id }}" wire:click="updateStatus({{ $order->id }}, 'preparing')">
```
Then tests become:
```php
->click('@start-preparing-' . $order->id)
```
This decouples tests from implementation details. Start with the most-used selectors (KDS buttons, POS payment buttons, guest menu cart).

---

## Finding 9: No Error/Edge Case Coverage

The suite tests mostly happy paths. Missing scenarios:

- **Network errors** — what happens when Livewire requests fail
- **Concurrent mutations** — two users acting on the same order
- **Expired sessions** — mid-flow session timeout
- **Empty states** — KDS with no orders, reports with no data, menu with no products
- **Boundary values** — orders at 0.000, products at max price, categories at limit
- **Permission escalation** — directly navigating to URLs beyond your role (partially covered by RbacAccessTest)

**Suggestion:** Don't add all of these now, but create a "Phase 4" backlog. Prioritize empty states and expired sessions — these are the most common real-world failure modes.

---

## Finding 10: `ShopSettingsTest` Uses Fragile `script()` Hack

```php
$browser->script("document.querySelector('form[wire...]').requestSubmit();");
```

This bypasses Dusk's interaction model. If the form selector changes, the `script()` call silently fails or throws an opaque error.

**Suggestion:** Replace with `->click()` on the submit button, or if the button is genuinely not interactable, investigate why (likely a z-index or visibility issue) and fix the UI rather than working around it in tests.

---

## Suggested Next Steps (Priority Order)

1. **Fix Phase 2 failures** — 12 tests broken. Run each individually, screenshot failures, fix selectors. Estimate: 1-2 hours.

2. **Delete or adopt Page Objects** — Remove the 14 unused Page Object files (430 lines of dead code), or wire them up. Don't leave dead code in the repo.

3. **Add `dusk` attributes to Blade templates** — Start with KDS buttons, POS payment buttons, and guest menu cart actions. Then migrate tests to use `@selector` syntax.

4. **Add Arabic locale test** — One test that switches the guest menu to Arabic and verifies product names render in Arabic.

5. **Add empty state tests** — KDS with no orders, reports dashboard with no data. These catch regressions in placeholder/empty-state UI.

6. **Document the CSS uppercase behavior** — Add a note to CLAUDE.md under Testing section so future test authors know to use uppercase text in Dusk assertions.

---

## Summary

The E2E test suite covers the right user flows and the Phase 1 tests are solid. The main issues are: Phase 2 is broken and needs a fix pass, Page Objects are dead code, selectors are brittle and coupled to Livewire internals, and the planning phase didn't account for CSS text transforms or verify actual UI capabilities before writing assertions. The test infrastructure (Dusk, MySQL `bite_testing`, SeedsTestData trait) is sound and scalable.

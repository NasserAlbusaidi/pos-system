# Sourdough Live Smoke Checklist

Use this checklist after #17 has a deployed Forge site, DNS resolves for
`getbite.om`, TLS is active, and `/health` is green. It is the operator packet
for #31 before the Sourdough walk-in.

Record the evidence in `docs/handoffs/sourdough.md` or the current handoff
record. Do not commit secrets, passwords, PINs, or backup credentials.

## 0. Preconditions

- `dig +short getbite.om` returns the Forge server IP.
- `curl -fsS https://getbite.om/health` returns HTTP 200.
- `curl -fsS https://getbite.om/menu/sourdough` returns the guest menu HTML.
- `php artisan bite:production-check` passes on the Forge server.
- `php artisan bite:schema-check` passes on the Forge server.
- `php artisan bite:log-check --minutes=60` passes before starting the smoke.
- `php artisan bite:handoff-check sourdough --minutes=60` passes.
- The Sourdough owner/admin password and staff PIN are available from the
  password manager.

## 1. Core Counter Flow

Target proof: PIN login -> POS order -> cash payment -> KDS transitions -> guest QR order -> tracking.

1. Open `https://getbite.om/pin`.
2. Sign in with the Sourdough staff PIN.
3. Create a small POS order with at least one Sourdough item.
4. Charge the order with cash.
5. Open KDS and confirm the paid order appears.
6. Move the order through the KDS states until ready.
7. Confirm the order status changes in the POS/dashboard view.
8. Capture the order ID and final status in the handoff record.

## 2. Guest QR Flow

1. Open the Sourdough guest QR route and verify it targets
   `https://getbite.om/menu/sourdough`.
2. Open `https://getbite.om/menu/sourdough` from a mobile network, not only the
   server or local Wi-Fi.
3. Place one guest QR order with a customer name, phone, line item, and order
   note.
4. Confirm the checkout redirects to the tokenized tracking page.
5. Confirm the order appears in POS and KDS.
6. Move it through KDS and confirm the tracking page updates.
7. Record the QR URL, tracker URL, and order ID.

## 3. Mobile Browsers

Run the guest QR flow on both target browsers:

- iOS Safari
- Android Chrome

For each browser, check:

- Home screen loads without horizontal overflow.
- Full menu, product sheet, cart, checkout, and tracking fit the viewport.
- Quantity controls and checkout buttons are tappable.
- The language switch remains reachable.
- No text overlaps in the hero, product cards, cart, checkout, or tracker.

## 4. Arabic / RTL

Run Arabic / RTL on every guest screen:

1. Switch the guest menu to Arabic.
2. Confirm the document direction is RTL.
3. Confirm Arabic product names render for Sourdough items.
4. Open product detail, cart, checkout, and tracker in Arabic.
5. Confirm no raw translation keys are visible.
6. Confirm layout direction, spacing, and button text are usable on mobile.

## 5. Images And Fallbacks

Product image fallback + missing cover/logo checks:

1. Confirm rendered product images load from `/storage/...` URLs with HTTP 200
   and an `image/*` content type.
2. Confirm thumb/card/full variants are visible in the guest menu where used.
3. Temporarily inspect a product without a custom image and confirm the fallback
   state is polished.
4. Inspect a shop state with missing cover/logo and confirm the guest home still
   renders without a broken image or layout collapse.
5. Record at least one successful product image URL and one fallback screenshot
   reference in the handoff record.

## 6. Exit Criteria

- Core counter flow passed.
- Guest QR flow passed.
- iOS Safari passed.
- Android Chrome passed.
- Arabic / RTL passed.
- Product image fallback + missing cover/logo passed.
- `php artisan bite:log-check --minutes=60` still passes after the smoke.
- Any failed item is filed as a GitHub issue before handoff.

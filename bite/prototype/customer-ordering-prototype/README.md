# Bite Menu V1

Standalone mobile-first prototype for the customer-facing QR ordering flow. It is intentionally separate from the main POS code so it can be reviewed, adjusted, and later integrated without depending on the current repo state.

## What This Covers

- Bite-branded customer shell with restaurant content inside it.
- QR/table context through URL params, for example `?table=12`.
- Restaurant header, search, categories, featured items, menu list.
- Item detail sheet with size, add-ons, notes, and quantity.
- Sticky cart bar, cart review, checkout with required name/phone, and order confirmation.
- English/Arabic UI toggle.
- Call waiter action.

## Files

- `index.html` - static page structure.
- `styles.css` - Bite visual system and responsive layout.
- `app.js` - sample menu data and local ordering behavior.
- `assets/brand/` - cropped Bite wordmark and mark.
- `assets/icons/` - selected Bite icon assets copied from the desktop asset folder.

## Bite Brand Tokens

Use these as the base platform identity:

- Primary: `#004225`
- Secondary: `#0B6B2E`
- Accent green: `#37B34A`
- Accent lime: `#7AC70C`
- Accent olive: `#B7C40D`
- Surface: white
- Radius: `8px`

The restaurant should control logo, name, cover image, menu items, category images, and item photos. Bite should keep ownership of the shell, cart, checkout, language behavior, and confirmation experience.

## Data Shape To Connect Later

The prototype currently uses local data in `app.js`. A backend can replace it with:

```js
{
  restaurant: {
    id: "minimo-cafe",
    name: "Minimo Cafe",
    nameAr: "مينيمو كافيه",
    type: "Coffee & Sweets",
    logoUrl: "...",
    coverUrl: "...",
    hours: "Open until 11:00 PM"
  },
  table: {
    id: "table-12",
    label: "12"
  },
  categories: [
    { id: "coffee", label: "Coffee", labelAr: "قهوة", iconUrl: "..." }
  ],
  products: [
    {
      id: "signature-latte",
      category: "coffee",
      name: "Signature Latte",
      nameAr: "لاتيه خاص",
      description: "...",
      descriptionAr: "...",
      price: 2.1,
      currency: "OMR",
      imageUrl: "...",
      tags: ["Popular"],
      sizes: [{ id: "regular", label: "Regular", priceDelta: 0 }],
      addOns: [{ id: "oat-milk", label: "Oat milk", priceDelta: 0.3 }]
    }
  ]
}
```

## API Hooks Needed

- `GET /restaurants/:slug/menu?table=12` - load restaurant, table, categories, and products.
- `POST /orders` - submit table order with line items, modifiers, item/order notes, required customer name/phone, and payment method.
- `GET /orders/:id` - optional customer order status.
- `POST /tables/:id/call-waiter` - optional waiter call.

## Local Preview

This is a static prototype. Open `index.html` directly, or serve the folder with any static server if browser security rules block local assets.

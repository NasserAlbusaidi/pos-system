# Arabic UI / RTL Layout — Design Document

**Date:** 2026-03-13
**Status:** Approved

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement the implementation plan that follows this design.

**Goal:** Full RTL support for guest and admin pages so Arabic-speaking users get a native right-to-left interface.

**Architecture:** CSS logical properties for automatic directional flipping, a single `SetLocale` middleware for consistent locale resolution, session-based per-user overrides with shop branding as the default. Super admin stays English.

---

## Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| RTL scope | Guest + Admin (not super admin) | Customers and shop staff need Arabic; super admin is internal tooling |
| Locale source of truth | Shop `branding['language']` | Persisted, shop-wide default |
| Per-user override | Session only (`admin_locale` / `guest_locale`) | No migration needed; lightweight |
| Super admin locale | Always English | Internal tool, minimal user benefit |
| CSS strategy | Logical properties (Approach A) | Zero duplication, future-proof, manageable audit (~427 lines) |
| Admin toggle placement | Bottom of sidebar nav | Always visible, low friction |
| Email RTL | Out of scope | Poor client support for logical properties |
| Translation scope | Visible UI (nav, headings, buttons, labels) | Don't boil the ocean |

---

## 1. Locale Resolution

### Resolution Order

| Context | Priority 1 | Priority 2 | Fallback |
|---------|-----------|-----------|----------|
| Guest pages | `session('guest_locale')` | `shop.branding['language']` | `'en'` |
| Admin pages | `session('admin_locale')` | `shop.branding['language']` | `'en'` |
| Super admin | — | — | `'en'` (hardcoded) |

### Middleware

One `SetLocale` middleware on all web routes:

- Determines context (guest, admin, super admin) from the route/auth state
- Reads the appropriate session key
- Falls back to shop branding, then `'en'`
- Calls `App::setLocale($locale)`
- Shares `$direction` (`'rtl'` or `'ltr'`) with all views
- Replaces current ad-hoc per-component locale setting

---

## 2. CSS Strategy

### Logical Properties Migration

Convert all directional properties in `app.css` to logical equivalents:

| Current | Replacement |
|---------|-------------|
| `left: 0` / `right: 0` | `inset-inline-start: 0` / `inset-inline-end: 0` |
| `border-right-color` | `border-inline-end-color` |
| `transform-origin: left` | `transform-origin: start` |
| `text-align: left` / `right` | `text-align: start` / `end` |
| `margin-left` / `margin-right` | `margin-inline-start` / `margin-inline-end` |
| `padding-left` / `padding-right` | `padding-inline-start` / `padding-inline-end` |

### What Stays

Keep `[dir="rtl"]` rules for non-directional concerns:
- Font-family swap (IBM Plex Sans Arabic)
- Letter-spacing reduction (wide tracking looks wrong in Arabic)

### Tailwind Directional Classes

Audit Blade views and replace:
- `pl-*` / `pr-*` → `ps-*` / `pe-*`
- `ml-*` / `mr-*` → `ms-*` / `me-*`
- `left-*` / `right-*` → `start-*` / `end-*`
- `text-left` / `text-right` → `text-start` / `text-end`

### Inline Styles

- `welcome.blade.php`: 4 decorative blobs — `left:` → `inset-inline-start:`, `right:` → `inset-inline-end:`
- `invoices/order.blade.php`: 1 instance — `text-align: right` → `text-align: end`
- Email template: Skip (out of scope)

---

## 3. UI Components

### Admin Sidebar Toggle

- Location: Bottom of `admin-navigation` component, above logout
- Appearance: Reuses `.lang-toggle` / `.lang-toggle-active` CSS classes
- Action: `switchLocale('en'|'ar')` → writes `session('admin_locale')` → full page reload
- Full reload required because `dir` attribute is on `<html>` tag

### Guest Menu Toggle

- Already exists and works. No changes.

### Super Admin

- No toggle. Hardcoded English.

---

## 4. Translation Strings

### Current State

`lang/en/` and `lang/ar/` each have: `common.php`, `pos.php`, `guest.php` (~200 lines total per locale).

### New Files

- `lang/en/admin.php` — Admin UI strings
- `lang/ar/admin.php` — Arabic translations

### Coverage

Prioritize visible UI text:
- Sidebar navigation labels
- Page headings
- Form labels and buttons
- Flash/status messages
- Dashboard metric labels

Deeply nested or rarely-seen text can remain English initially.

### Migration

Replace hardcoded English strings in admin Blade views with `{{ __('admin.key') }}` calls.

---

## 5. Non-Goals

- Email template RTL
- Super admin RTL
- Per-user database persistence (no migration)
- Full translation of every string
- Dusk tests for RTL (follow-up)

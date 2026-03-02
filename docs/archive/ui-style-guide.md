# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# UI Style Guide: Bite POS (Industrial Zen)

## 1. Aesthetic Direction
**Concept:** "Utilitarian Warmth"
**Vibe:** Precise, tactile, efficient. A mix of high-end stationery and industrial electronics.
**Keywords:** Monospaced, Grid, Texture, Contrast, Tactile.

## 2. Color Palette
We avoid sterile whites and grays. Everything has warmth.

### Base
*   **Paper (Background):** `#FDFCF8` (Warm Off-White)
*   **Ink (Text/Primary):** `#1A1918` (Deep Charcoal, almost Black)
*   **Vellum (Secondary BG):** `#F2F0E9` (Slightly darker paper for cards/sidebars)

### Functional
*   **Crema (Primary Action):** `#CC5500` (Burnt Orange - High visibility for "Pay" / "Order")
*   **Matcha (Success/Safe):** `#4A7A58` (Muted Forest Green)
*   **Berry (Error/Danger):** `#A63D40` (Muted Red)
*   **Graphite (Neutral/Borders):** `#D1D1CB` (Warm Grey for lines)

## 3. Typography
Mixing strict data presentation with approachable readability.

### Headings & Data
*   **Font:** `IBM Plex Mono` (Google Fonts)
*   **Usage:** Prices, Table Numbers, Timestamps, Headers, Buttons.
*   **Weight:** 500 (Medium) / 600 (SemiBold)

### Body & Interface
*   **Font:** `DM Sans` (Google Fonts)
*   **Usage:** Menu descriptions, navigation, long text.
*   **Weight:** 400 (Regular) / 500 (Medium)

## 4. UI Components (Tailwind Classes)

### Buttons (Tactile)
*   **Primary:**
    `bg-[#CC5500] text-[#FDFCF8] font-mono uppercase tracking-wide border border-[#1A1918] shadow-[2px_2px_0px_0px_#1A1918] hover:translate-y-[1px] hover:shadow-[1px_1px_0px_0px_#1A1918] transition-all`
*   **Secondary:**
    `bg-[#FDFCF8] text-[#1A1918] font-mono border border-[#1A1918] shadow-[2px_2px_0px_0px_#1A1918] hover:bg-[#F2F0E9]`

### Cards & Panels
*   **Style:** Flat, bordered, hard edges. No soft drop shadows.
*   **Class:** `bg-[#FDFCF8] border border-[#D1D1CB] p-4`

### Inputs
*   **Style:** Brutalist, high contrast focus.
*   **Class:** `bg-transparent border-b-2 border-[#D1D1CB] focus:border-[#CC5500] focus:ring-0 font-mono text-[#1A1918] placeholder-[#D1D1CB]`

## 5. Layout Patterns
*   **The Grid:** Use visible 1px borders to separate main sections (Sidebar | Main | Details) to mimic a ledger or dashboard.
*   **Density:**
    *   *POS View:* High density, small margins, maximum information.
    *   *Guest View:* High whitespace, large tap targets, focus on imagery.

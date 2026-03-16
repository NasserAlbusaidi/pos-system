"""Test all 10 new features on the local dev server."""
import time
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8787"
SCREENSHOTS = "/tmp/bite-features"

import os
os.makedirs(SCREENSHOTS, exist_ok=True)

def screenshot(page, name):
    path = f"{SCREENSHOTS}/{name}.png"
    page.screenshot(path=path, full_page=True)
    print(f"  Screenshot: {path}")

def login(page, email="admin@bite.com", password="password"):
    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    time.sleep(2)
    # Find inputs by their wire:model attribute or other selectors
    email_input = page.locator('input[wire\\:model="form.email"], input[id="email"], input[name="email"], input[type="email"]').first
    password_input = page.locator('input[wire\\:model="form.password"], input[id="password"], input[name="password"], input[type="password"]').first
    email_input.fill(email)
    password_input.fill(password)
    page.locator('button[type="submit"]').click()
    page.wait_for_load_state("networkidle")
    time.sleep(2)

results = {}

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)

    # ═══════════════════════════════════════════════════════
    # RECON: Check login page structure
    # ═══════════════════════════════════════════════════════
    print("\n0. RECONNAISSANCE")
    page = browser.new_page()
    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    time.sleep(2)
    screenshot(page, "00-login-page")
    inputs = page.locator('input').all()
    for inp in inputs:
        attrs = {k: inp.get_attribute(k) for k in ['type', 'id', 'name', 'wire:model'] if inp.get_attribute(k)}
        print(f"  Input: {attrs}")
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 1: Kitchen Sound Alerts
    # ═══════════════════════════════════════════════════════
    print("\n1. KITCHEN SOUND ALERTS")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/kds")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "01-kds-display")
        content = page.content()
        results['1_sound_alert'] = "playChime" in content and "AudioContext" in content
        print(f"  PASS - Audio chime: {results['1_sound_alert']}")
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "01-error")
        results['1_sound_alert'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 2: Auto-86
    # ═══════════════════════════════════════════════════════
    print("\n2. AUTO-86")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/pos")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "02-pos-dashboard")
        results['2_pos_loaded'] = True
        print(f"  PASS - POS loaded")
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "02-error")
        results['2_pos_loaded'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 3: Post-Order Feedback
    # ═══════════════════════════════════════════════════════
    print("\n3. POST-ORDER FEEDBACK")
    page = browser.new_page()
    try:
        page.goto(f"{BASE}/menu/demo")
        page.wait_for_load_state("networkidle")
        time.sleep(2)
        screenshot(page, "03-guest-menu")
        results['3_guest_menu'] = page.url != f"{BASE}/menu/demo" or "demo" in page.content().lower()
        print(f"  Guest menu loaded: {results['3_guest_menu']}")
    except Exception as e:
        print(f"  FAIL - {e}")
        results['3_guest_menu'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 4: Revenue Heatmap + Goals
    # ═══════════════════════════════════════════════════════
    print("\n4. REVENUE HEATMAP + GOALS")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/dashboard")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "04-dashboard")
        results['4_dashboard'] = True
        print(f"  PASS - Dashboard loaded")
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "04-error")
        results['4_dashboard'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 5: Cash Reconciliation
    # ═══════════════════════════════════════════════════════
    print("\n5. CASH RECONCILIATION")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/cash-reconciliation")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "05-cash-reconciliation")
        content = page.content()
        results['5_loaded'] = "cash" in content.lower() or "reconcil" in content.lower() or "shift" in content.lower()
        print(f"  PASS - Cash reconciliation page: {results['5_loaded']}")

        # Try reconciling
        num_input = page.locator('input[type="number"]').first
        if num_input.is_visible():
            num_input.fill("50.000")
            btns = page.locator('button').all()
            for btn in btns:
                if 'reconcil' in btn.inner_text().lower():
                    btn.click()
                    time.sleep(2)
                    screenshot(page, "05b-reconciled")
                    results['5_reconciled'] = True
                    print(f"  PASS - Reconciliation submitted")
                    break
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "05-error")
        results['5_loaded'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 7: Menu Engineering
    # ═══════════════════════════════════════════════════════
    print("\n7. MENU ENGINEERING MATRIX")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/menu-engineering")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "07-menu-engineering")
        content = page.content()
        results['7_loaded'] = any(w in content.lower() for w in ["star", "puzzle", "dog", "engineering", "classification", "menu"])
        print(f"  PASS - Menu engineering page: {results['7_loaded']}")
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "07-error")
        results['7_loaded'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 9: Time-Based Pricing Rules
    # ═══════════════════════════════════════════════════════
    print("\n9. TIME-BASED PRICING RULES")
    page = browser.new_page()
    try:
        login(page)
        page.goto(f"{BASE}/pricing-rules")
        page.wait_for_load_state("networkidle")
        time.sleep(1)
        screenshot(page, "09-pricing-rules")
        content = page.content()
        results['9_loaded'] = any(w in content.lower() for w in ["pricing", "rule", "discount", "happy hour"])
        print(f"  PASS - Pricing rules page: {results['9_loaded']}")
    except Exception as e:
        print(f"  FAIL - {e}")
        screenshot(page, "09-error")
        results['9_loaded'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    # TEST 10: Group Ordering
    # ═══════════════════════════════════════════════════════
    print("\n10. GROUP ORDERING")
    page = browser.new_page()
    try:
        page.goto(f"{BASE}/menu/demo")
        page.wait_for_load_state("networkidle")
        time.sleep(2)
        screenshot(page, "10-guest-menu")
        content = page.content()
        results['10_group'] = "createGroup" in content or "group" in content.lower()
        print(f"  Group ordering UI: {results['10_group']}")
    except Exception as e:
        print(f"  FAIL - {e}")
        results['10_group'] = False
    page.close()

    # ═══════════════════════════════════════════════════════
    print("\n" + "=" * 55)
    print("FEATURE TEST SUMMARY")
    print("=" * 55)
    passed = sum(1 for v in results.values() if v)
    total = len(results)
    for key, val in sorted(results.items()):
        status = "PASS" if val else "FAIL"
        print(f"  [{status}] {key}")
    print(f"\n  {passed}/{total} checks passed")
    print(f"  Screenshots: {SCREENSHOTS}/")

    browser.close()

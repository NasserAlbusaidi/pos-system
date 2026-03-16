"""Quick visual check of the 5 polished features."""
import time
from playwright.sync_api import sync_playwright

BASE = "http://127.0.0.1:8787"
SCREENSHOTS = "/tmp/bite-polish"
import os
os.makedirs(SCREENSHOTS, exist_ok=True)

def login(page):
    page.goto(f"{BASE}/login")
    page.wait_for_load_state("networkidle")
    time.sleep(2)
    page.fill('#email', 'admin@bite.com')
    page.fill('#password', 'password')
    page.locator('button[type="submit"]').click()
    page.wait_for_load_state("networkidle")
    time.sleep(2)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)

    # Dashboard: Heatmap + Goal
    print("1. Dashboard with Heatmap + Goal")
    page = browser.new_page()
    login(page)
    page.goto(f"{BASE}/dashboard")
    page.wait_for_load_state("networkidle")
    time.sleep(1)
    page.screenshot(path=f"{SCREENSHOTS}/dashboard-full.png", full_page=True)
    content = page.content()
    has_goal = "goal" in content.lower() or "dailyGoal" in content
    has_heatmap = "heatmap" in content.lower()
    print(f"  Goal UI: {has_goal}")
    print(f"  Heatmap UI: {has_heatmap}")
    page.close()

    # POS: Auto-86 + Upsell
    print("\n2. POS with Auto-86 + Upsell")
    page = browser.new_page()
    login(page)
    page.goto(f"{BASE}/pos")
    page.wait_for_load_state("networkidle")
    time.sleep(1)
    page.screenshot(path=f"{SCREENSHOTS}/pos-full.png", full_page=True)
    content = page.content()
    has_86 = "86" in content or "toggle86" in content
    has_upsell = "upsell" in content.lower() or "also order" in content.lower()
    print(f"  86 toggle: {has_86}")
    print(f"  Upsell area: {has_upsell}")
    page.close()

    # Guest menu: time-based pricing + group ordering
    print("\n3. Guest Menu (pricing + group)")
    page = browser.new_page()
    page.goto(f"{BASE}/menu/demo")
    page.wait_for_load_state("networkidle")
    time.sleep(2)
    page.screenshot(path=f"{SCREENSHOTS}/guest-menu-full.png", full_page=True)
    content = page.content()
    has_group = "group" in content.lower()
    print(f"  Group ordering: {has_group}")
    page.close()

    print(f"\nScreenshots: {SCREENSHOTS}/")
    browser.close()

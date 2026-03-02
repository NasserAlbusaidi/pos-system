# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Product Proposal: Next-Gen Coffee Shop POS

## 1. Executive Summary
**Bite** is a modern, web-based Point of Sale (POS) system designed to solve the critical reliability and stability issues found in legacy solutions like Fascano. Targeted initially at coffee shops and small retail environments, Bite provides a high-uptime, crash-free experience that seamlessly integrates customer self-ordering with traditional counter-based payments.

**Key Objectives:**
*   **Reliability First:** Built to ensure the system remains operational during peak hours, directly addressing the primary failure point of previous systems.
*   **Hybrid Workflow:** Streamlines operations by allowing customers to scan QR codes and order directly from their phones, with the Kitchen Display System (KDS) and POS receiving orders instantly for counter checkout.
*   **Cost Efficiency:** A hardware-agnostic approach that runs on standard tablets and smartphones, reducing initial setup costs for business owners.
*   **SaaS Ready:** Designed with a multi-tenant architecture from the ground up, allowing for rapid scaling from a single pilot location to a full SaaS offering.
*   **Rapid Delivery:** Targeted MVP rollout within a 1-3 month window.

## 2. System Architecture & Tech Stack
Bite is engineered for high performance and rapid iteration using the **TALL Stack** (Tailwind, Alpine.js, Laravel, Livewire). This choice ensures a modern, reactive user experience while maintaining the development speed and security of the Laravel ecosystem.

**Core Stack:**
*   **Framework:** Laravel 11 (PHP 8.3+)
*   **Frontend (TALL):** Livewire 3 for "SPA-like" reactivity, Alpine.js for client-side interactions, and Tailwind CSS for a modern UI.
*   **Database & Isolation:** MySQL hosted on Google Cloud SQL. Data isolation is managed via a dedicated multi-tenancy package, utilizing middleware-level detection (via subdomains or custom domains) to ensure strict tenant separation.
*   **Infrastructure:** Deployed on **Google Cloud Platform (GCP)**, leveraging Cloud Run for scalable compute and Cloud SQL for managed database services. **Note:** The MVP is a cloud-only solution and requires an active internet connection for all operations.

**Operational Components:**
*   **Order Syncing:** A simple yet robust 5-10 second polling mechanism ensures the Kitchen Display System (KDS) and POS dashboards stay updated. This is the MVP approach to minimize complexity, with a roadmap to transition to WebSockets (Laravel Reverb/Pusher) as the user base scales.
*   **Cloud Printing:** Integration with a specialized printing service (e.g., PrintNode) enables silent, direct-to-thermal-printer receipt generation, bypassing standard browser print dialogs.
*   **Storage:** Google Cloud Storage (GCS) for efficient delivery of menu images and digital assets.

## 3. Core Features (MVP)
The MVP focus is on operational stability and a seamless "Scan-to-Pay" workflow.

### Ordering & Menu Management
*   **Dynamic Digital Menu:** A centralized dashboard for managing categories, items, and robust modifiers (variants, add-ons, and special instructions).
*   **QR-Based Guest Ordering:** A lightweight, no-login web interface for customers to browse the menu, build a cart, and submit orders directly to the kitchen/POS.
*   **Order Linking:** Automatic association of orders with specific table numbers (via QR code metadata) or customer pickup names.

### POS & Checkout Experience
*   **Live Order Dashboard:** A real-time interface for staff to manage incoming QR orders and enter manual walk-in orders.
*   **Advanced Bill Splitting:** A deep splitting engine allowing staff to divide bills by specific amount, by individual items, or by seat/guest.
*   **Payment Recording:** One-tap recording for "Cash" or "Credit" payments to facilitate accurate end-of-day sales tracking.
*   **Receipt Automation:** Automated printing to kitchen thermal printers for order prep and customer thermal printers for final receipts.

### Kitchen & Analytics
*   **Kitchen Display System (KDS):** A dedicated tablet interface for kitchen staff to track "Pending" and "Preparing" orders, with tap-to-update status ("Ready", "Completed").
*   **Sales Analytics:** Comprehensive reporting suite covering daily/weekly revenue, best-selling products, peak operational hours, and payment method distribution.

## 4. User Flows

### Flow A: Customer QR Order (Pay at Table)
1.  **Scan & Browse:** Customer scans the QR code at their table.
2.  **Order Submission:** Customer adds items to the cart and submits the order. No login required.
3.  **Confirmation:** The customer screen displays: "Order Placed! A server will arrive shortly to collect payment."

### Flow B: Server Payment & Activation
1.  **Notification:** The main POS dashboard alerts staff: "New Order at Table #4 (Unpaid)."
2.  **Order Expiry:** To prevent dashboard clutter, unpaid QR orders automatically expire and are removed from the system after **6 minutes** if no payment is recorded.
3.  **Payment Collection:** A server approaches the table with a portable payment terminal.
4.  **Validation:** The server confirms the order details and collects payment.
5.  **Activation:** The server marks the order as "Paid" on the POS. **Note:** Orders are only activated for the kitchen once the payment is 100% complete; partial payments are not supported in MVP.
6.  **Kitchen Trigger:** *Only upon full payment confirmation*, the order is released to the Kitchen Display System (KDS) to prevent waste.

### Flow C: Kitchen Fulfillment
1.  **Incoming Order:** The KDS polls for new *paid* orders and displays the ticket for Table #4.
2.  **Preparation:** Kitchen staff taps "Start Preparing" (optional status) or begins work.
3.  **Completion:** Once ready, staff taps "Complete," effectively clearing the ticket from the active KDS view.

### Flow D: Manual Walk-In Order
1.  **Entry:** Customer approaches the counter.
2.  **Order Input:** Staff enters the order manually via the POS interface.
3.  **Payment:** Staff collects payment immediately.
4.  **Auto-Dispatch:** The order is automatically sent to the KDS (bypassing the "Pending Payment" hold).

## 5. Future Roadmap
Beyond the core operational MVP, Bite will evolve into a comprehensive business management platform.

*   **Customer Loyalty & Accounts:** Implementation of a points-based loyalty system where customers can create accounts to track rewards and save "Favorite Orders" for one-tap reordering.
*   **Multi-Location Management:** A "Super Admin" dashboard allowing business owners to manage menu items, pricing, and reporting across multiple coffee shop locations from a single view.
*   **Inventory Tracking:** Ingredient-level stock management that deducts inventory (e.g., milk, beans, syrups) automatically based on recipes associated with sold items.
*   **Staff Management:** A module for employee clock-in/out, shift scheduling, and performance tracking (sales per server).

# Historical/Deprecated

> Archived on 2026-02-20.
> This file is retained for historical context and is not the active source of truth.
> Canonical product and engineering docs live under `bite/`.

# Feature Specification: Bite Platform Admin

## 1. Overview & Access Control
The **Platform Admin** is a privileged dashboard designed for the platform owners to manage the entire SaaS ecosystem. It is accessible via a dedicated route and protected by strict role-based access control.

*   **Access Route:** `/admin` (Redirects non-admins to 403 or home).
*   **Role Definition:** A user with `is_super_admin = true`. This role supersedes all shop-specific scoping.
*   **Security:** Protected by `auth` and a custom middleware `EnsureUserIsSuperAdmin`.

## 2. Dashboard & Global Analytics
The landing view for Platform Admin provides a high-level overview of the platform performance.

*   **Global Stats:**
    *   **Total Shops:** Count of all registered shops.
    *   **Active Shops:** Count of shops with 'active' status.
*   **System Status:**
    *   **Online/Offline:** Real-time health check of the system services.

## 3. Shop Management
Platform Admin allows full lifecycle management of Shops.

*   **Shop Directory:** A searchable and filterable list of all registered shops.
*   **Operational Controls:**
    *   **Suspend/Activate:** Instantly toggle a shop's availability.
    *   **Edit Details:** Ability to correct shop details, change URLs, or update owners.
    *   **Add Shop:** A tool to manually register new shops and generate their initial admin user.
    *   **Delete Shop:** Permanent deletion of a shop and its associated data.

## 4. Admin Access (Viewing as User)
Admin access allows support staff to see the exact state of a shop to troubleshoot issues.

*   **Workflow:**
    1.  Admin selects a Shop from the Directory.
    2.  Clicks **"Login As Owner"**.
    3.  System stores current Admin ID in session (`impersonator_id`) and switches the primary Auth user to the Shop's owner.
    4.  A **Persistent Banner** appears at the top: "Admin Access: Viewing as User | EXIT".
*   **Permissions:** While accessing as a user, the admin has the same permissions as the shop owner.

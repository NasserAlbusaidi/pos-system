# Bite Architecture

## 1. Tenancy Strategy
Bite is single-database, multi-tenant by `shop_id`.

Design rules:
- Every tenant-owned aggregate includes `shop_id`.
- Reads and writes must scope by current user's `shop_id`.
- Cross-shop identifiers in request payloads are rejected.

## 2. Authorization
Authorization is layered:
- Route middleware: `auth`, `verified`, and `role:*`
- Super-admin middleware for platform management
- Additional mutation guards in sensitive Livewire actions (status transitions/override flows)

RBAC matrix:
- `server|manager|admin`: POS + invoices
- `kitchen|manager|admin`: KDS transitions
- `manager|admin`: admin operational modules
- `super_admin`: platform-level shop/user management

## 3. Order and Payment Model
Order is the billing intent; payments are ledger entries.

Properties:
- Multiple payments may satisfy one order.
- `balance_due` derives from `total_amount - paid_total`.
- Status transitions are explicit.

Integrity controls:
- Stripe webhook processing is signed + idempotent.
- `webhook_events(provider,event_id)` prevents duplicate processing.
- Order mutation in webhook path uses row locking.

## 4. Privacy and Tracking
Guest order tracking is token-based:
- `orders.tracking_token` UUID (unique)
- Public route: `/track/{tracking_token}`
- Numeric/enumerable order IDs are not used in tracking URLs.

## 5. Fulfillment Idempotency
Completion is modeled with `orders.fulfilled_at`.

Guarantees:
- `ready -> completed` transition can succeed once.
- Inventory consumption and receipt printing run only on the first successful fulfillment.

## 6. Runtime Components
- Web app: Laravel + Livewire
- Queue-backed jobs/services
- Scheduled task: cancel expired unpaid orders every minute
- Optional PrintNode integration for receipt/kitchen printing

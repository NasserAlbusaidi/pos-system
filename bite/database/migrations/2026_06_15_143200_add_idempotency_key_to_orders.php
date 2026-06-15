<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a per-checkout idempotency key to orders (Phase 7a, #28).
     *
     * A double-click, network retry, or replayed Livewire request must not
     * create duplicate guest orders. GuestMenu generates a token when the
     * review/checkout sheet opens and sends it with submitOrder; the insert
     * is guarded by the (shop_id, idempotency_key) UNIQUE index (same house
     * style as webhook_events.unique(provider, event_id)). Scoping to shop_id
     * keeps tenants isolated: a token only collides within its own shop, never
     * across shops. On a replayed submit the
     * insert collides, and the component redirects to the already-created
     * order's tracker instead of duplicating.
     *
     * Nullable so existing rows and any non-guest order creation path (POS,
     * splits) are unaffected — only guest checkouts set the key.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('tracking_token');
            $table->unique(['shop_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};

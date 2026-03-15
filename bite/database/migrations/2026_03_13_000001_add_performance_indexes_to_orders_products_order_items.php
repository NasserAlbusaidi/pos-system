<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: FK constraints (shop_id, order_id, product_id) auto-create indexes
     * in MySQL. We use Schema::hasIndex() checks to skip those if they already
     * exist, while still adding them for SQLite (used in tests).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasIndex('orders', ['shop_id'])) {
                $table->index('shop_id');
            }

            $table->index('status');
            $table->index('created_at');
            $table->index('paid_at');
            $table->index(['shop_id', 'status'], 'orders_shop_id_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasIndex('products', ['shop_id'])) {
                $table->index('shop_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasIndex('order_items', ['order_id'])) {
                $table->index('order_id');
            }

            if (! Schema::hasIndex('order_items', ['product_id'])) {
                $table->index('product_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Only drop indexes this migration created.
            // The composite index and non-FK single-column indexes are always ours.
            $table->dropIndex('orders_shop_id_status_index');
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);

            // shop_id index may have been pre-existing from FK; only drop if
            // it uses the standard Laravel index name (not the FK index name).
            if (Schema::hasIndex('orders', 'orders_shop_id_index')) {
                $table->dropIndex('orders_shop_id_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasIndex('products', 'products_shop_id_index')) {
                $table->dropIndex('products_shop_id_index');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasIndex('order_items', 'order_items_order_id_index')) {
                $table->dropIndex('order_items_order_id_index');
            }

            if (Schema::hasIndex('order_items', 'order_items_product_id_index')) {
                $table->dropIndex('order_items_product_id_index');
            }
        });
    }
};

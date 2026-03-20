<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // C1: Fix decimal precision for OMR (3 decimal places)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 3)->change();
            $table->decimal('discount_value', 10, 3)->nullable()->change();
            $table->decimal('tax_rate', 5, 3)->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 10, 3)->change();
            $table->decimal('subtotal_amount', 10, 3)->default(0)->change();
            $table->decimal('tax_amount', 10, 3)->default(0)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('price_snapshot', 10, 3)->change();
        });

        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->decimal('price_adjustment_snapshot', 10, 3)->change();
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->decimal('price_adjustment', 10, 3)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 10, 3)->change();
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 3)->default(0)->change();
        });

        // C9: Protect financial data from accidental shop deletion.
        // Change cascadeOnDelete → restrictOnDelete on financial tables.
        // Skip on SQLite (staging) since it doesn't support dropping foreign keys.
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->restrictOnDelete();
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->restrictOnDelete();
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->restrictOnDelete();
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->restrictOnDelete();
            });
        }

        // H7a: Index audit_logs for shop-scoped queries with date ordering and action filtering
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['shop_id', 'created_at']);
            $table->index(['shop_id', 'action']);
        });

        // H7b: Index payments.paid_at for ShiftReport queries
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['shop_id', 'paid_at']);
        });

        // H7c: Index orders for scheduler (cancelExpired) and guest tracking
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'expires_at'], 'orders_status_expires_at_index');
            $table->index(['shop_id', 'loyalty_phone'], 'orders_shop_loyalty_phone_index');
        });

        // H8: Prevent duplicate pivot table entries
        Schema::table('product_modifier_group', function (Blueprint $table) {
            $table->unique(['product_id', 'modifier_group_id']);
        });

        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->unique(['ingredient_id', 'product_id']);
        });

        // H6: Index group_carts for cleanup queries
        Schema::table('group_carts', function (Blueprint $table) {
            $table->index('expires_at');
        });

        // H7d: Index pricing_rules for the activeNow scope
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->index(
                ['shop_id', 'is_active', 'start_time', 'end_time'],
                'pricing_rules_shop_active_time_index'
            );
        });

        // M5: Index notifications for unread count queries
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'read_at']);
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropIndex('pricing_rules_shop_active_time_index');
        });

        Schema::table('group_carts', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
        });

        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->dropUnique(['ingredient_id', 'product_id']);
        });

        Schema::table('product_modifier_group', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'modifier_group_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_expires_at_index');
            $table->dropIndex('orders_shop_loyalty_phone_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'paid_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'created_at']);
            $table->dropIndex(['shop_id', 'action']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            });

            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            });
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->decimal('price_adjustment', 10, 2)->change();
        });

        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->decimal('price_adjustment_snapshot', 10, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('price_snapshot', 10, 2)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total_amount', 10, 2)->change();
            $table->decimal('subtotal_amount', 10, 2)->default(0)->change();
            $table->decimal('tax_amount', 10, 2)->default(0)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->change();
            $table->decimal('discount_value', 10, 2)->nullable()->change();
            $table->decimal('tax_rate', 5, 2)->nullable()->change();
        });
    }
};

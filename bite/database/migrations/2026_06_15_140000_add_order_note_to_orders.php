<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a guest-supplied, order-level note to orders.
     *
     * Sourdough pilot (Phase 4, #24): alongside the per-line item notes added
     * in Phase 3, guests can leave one note for the whole order ("table by the
     * window", "ring when ready", a shared allergen flag). It must reach the
     * kitchen (KDS card + printed ticket). Nullable text; trimmed + capped at
     * 500 chars in the application layer where the untrusted input lands.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('order_note')->nullable()->after('customer_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_note');
        });
    }
};

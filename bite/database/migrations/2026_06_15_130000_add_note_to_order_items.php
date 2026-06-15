<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a guest-supplied item note to order_items.
     *
     * Pulled into the Sourdough pilot for bakery allergen safety: guests can
     * flag "no nuts / less salt / allergy" per line, and that note must reach
     * the kitchen (KDS + printed ticket). Nullable string, capped at 255 in
     * the application layer where the untrusted input is trimmed.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('note', 255)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};

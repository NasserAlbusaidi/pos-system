<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('discount_value', 10, 2)->nullable()->after('price');
            $table->string('discount_type')->default('fixed')->after('discount_value'); // fixed, percentage
            $table->boolean('is_on_sale')->default(false)->after('discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['discount_value', 'discount_type', 'is_on_sale']);
        });
    }
};

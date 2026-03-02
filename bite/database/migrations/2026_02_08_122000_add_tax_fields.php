<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('branding');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->nullable()->after('price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 10, 2)->default(0)->after('total_amount');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('subtotal_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tax_rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'tax_amount']);
        });
    }
};

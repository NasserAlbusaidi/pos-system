<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('currency_code', 3)->default('OMR')->after('tax_rate');
            $table->string('currency_symbol', 10)->default('OMR')->after('currency_code');
            $table->tinyInteger('currency_decimals')->default(3)->after('currency_symbol');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_decimals']);
        });
    }
};

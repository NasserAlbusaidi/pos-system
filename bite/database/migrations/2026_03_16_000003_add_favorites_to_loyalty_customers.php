<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_customers', function (Blueprint $table) {
            $table->json('favorites')->nullable()->after('points');
            $table->integer('visit_count')->default(0)->after('points');
            $table->timestamp('last_visit_at')->nullable()->after('visit_count');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_customers', function (Blueprint $table) {
            $table->dropColumn(['favorites', 'visit_count', 'last_visit_at']);
        });
    }
};

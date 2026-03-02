<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'table_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('table_number');
            });
        }

        if (Schema::hasTable('tables')) {
            Schema::drop('tables');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tables')) {
            Schema::create('tables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->float('x');
                $table->float('y');
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('orders', 'table_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('table_number')->nullable()->after('shop_id');
            });
        }
    }
};

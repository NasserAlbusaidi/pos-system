<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('tracking_token')->nullable()->after('split_group_id');
            $table->timestamp('fulfilled_at')->nullable()->after('paid_at');
        });

        DB::table('orders')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['tracking_token' => (string) Str::uuid()]);
                }
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('tracking_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('orders_tracking_token_unique');
            $table->dropColumn(['tracking_token', 'fulfilled_at']);
        });
    }
};

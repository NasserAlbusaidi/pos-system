<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_reference')->nullable()->after('method');
            $table->unsignedBigInteger('reverses_payment_id')->nullable()->after('created_by');

            $table->index('provider_reference', 'payments_provider_reference_index');
            $table->index('reverses_payment_id', 'payments_reverses_payment_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_provider_reference_index');
            $table->dropIndex('payments_reverses_payment_id_index');
            $table->dropColumn(['provider_reference', 'reverses_payment_id']);
        });
    }
};

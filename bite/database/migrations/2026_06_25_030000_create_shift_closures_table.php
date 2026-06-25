<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('expected_cash', 10, 3);
            $table->decimal('actual_cash', 10, 3);
            $table->decimal('difference', 10, 3);
            $table->json('shift_summary');
            $table->text('notes')->nullable();
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['shop_id', 'business_date'], 'shift_closures_shop_business_date_unique');
            $table->index(['shop_id', 'closed_at'], 'shift_closures_shop_closed_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_closures');
    }
};

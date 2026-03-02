<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('phone');
            $table->string('name')->nullable();
            $table->integer('points')->default(0);
            $table->timestamps();
            $table->unique(['shop_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_customers');
    }
};

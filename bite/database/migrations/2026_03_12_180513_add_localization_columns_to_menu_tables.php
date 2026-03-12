<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name_en');
            $table->renameColumn('description', 'description_en');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->text('description_ar')->nullable()->after('description_en');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name_en');
        });

        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });
        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name_en');
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });
        Schema::table('modifier_options', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name_en');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->renameColumn('product_name_snapshot', 'product_name_snapshot_en');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('product_name_snapshot_ar')->nullable()->after('product_name_snapshot_en');
        });

        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->renameColumn('modifier_option_name_snapshot', 'modifier_option_name_snapshot_en');
        });
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->string('modifier_option_name_snapshot_ar')->nullable()->after('modifier_option_name_snapshot_en');
        });
    }

    public function down(): void
    {
        Schema::table('order_item_modifiers', function (Blueprint $table) {
            $table->dropColumn('modifier_option_name_snapshot_ar');
            $table->renameColumn('modifier_option_name_snapshot_en', 'modifier_option_name_snapshot');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_name_snapshot_ar');
            $table->renameColumn('product_name_snapshot_en', 'product_name_snapshot');
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->dropColumn('name_ar');
            $table->renameColumn('name_en', 'name');
        });

        Schema::table('modifier_groups', function (Blueprint $table) {
            $table->dropColumn('name_ar');
            $table->renameColumn('name_en', 'name');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('name_ar');
            $table->renameColumn('name_en', 'name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'description_ar']);
            $table->renameColumn('name_en', 'name');
            $table->renameColumn('description_en', 'description');
        });
    }
};

<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MoneyDecimalSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_precision_migration_expands_legacy_omr_columns_to_three_decimals(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_03_20_000001_production_readiness_fixes.php'));

        $columns = [
            'products' => ['price', 'discount_value'],
            'orders' => ['total_amount', 'subtotal_amount', 'tax_amount'],
            'order_items' => ['price_snapshot'],
            'order_item_modifiers' => ['price_adjustment_snapshot'],
            'modifier_options' => ['price_adjustment'],
            'payments' => ['amount'],
        ];

        foreach ($columns as $table => $tableColumns) {
            foreach ($tableColumns as $column) {
                $this->assertMatchesRegularExpression(
                    "/Schema::table\\('{$table}'.*?\\\$table->decimal\\('{$column}',\\s*\\d+,\\s*3\\).*?->change\\(\\)/s",
                    $migration,
                    "{$table}.{$column} must be expanded to 3 decimal places for OMR.",
                );
            }
        }
    }

    public function test_database_preserves_three_decimal_money_values(): void
    {
        $shop = Shop::factory()->create();
        $category = Category::factory()->create(['shop_id' => $shop->id]);
        $product = Product::factory()->create([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'price' => '1.235',
            'discount_value' => '0.125',
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'shop_id' => $shop->id,
            'status' => 'paid',
            'total_amount' => '1.235',
            'subtotal_amount' => '1.110',
            'tax_amount' => '0.125',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderItemId = DB::table('order_items')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_name_snapshot_en' => 'Precision shawarma',
            'price_snapshot' => '1.235',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $modifierGroupId = DB::table('modifier_groups')->insertGetId([
            'shop_id' => $shop->id,
            'name_en' => 'Sauces',
            'min_selection' => 0,
            'max_selection' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('modifier_options')->insert([
            'modifier_group_id' => $modifierGroupId,
            'name_en' => 'Smoked toum',
            'price_adjustment' => '0.125',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('order_item_modifiers')->insert([
            'order_item_id' => $orderItemId,
            'modifier_option_name_snapshot_en' => 'Smoked toum',
            'price_adjustment_snapshot' => '0.125',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'shop_id' => $shop->id,
            'order_id' => $orderId,
            'amount' => '1.235',
            'method' => 'cash',
            'paid_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pricing_rules')->insert([
            'shop_id' => $shop->id,
            'category_id' => $category->id,
            'product_id' => $product->id,
            'name' => 'Lunch fils',
            'discount_type' => 'fixed',
            'discount_value' => '0.125',
            'start_time' => '11:00:00',
            'end_time' => '15:00:00',
            'days_of_week' => json_encode([1, 2, 3, 4, 5]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shift_closures')->insert([
            'shop_id' => $shop->id,
            'business_date' => now()->toDateString(),
            'expected_cash' => '1.235',
            'actual_cash' => '1.110',
            'difference' => '-0.125',
            'shift_summary' => json_encode(['cash' => '1.235']),
            'closed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertMoneyValue('products', 'price', '1.235');
        $this->assertMoneyValue('products', 'discount_value', '0.125');
        $this->assertMoneyValue('orders', 'total_amount', '1.235');
        $this->assertMoneyValue('orders', 'subtotal_amount', '1.110');
        $this->assertMoneyValue('orders', 'tax_amount', '0.125');
        $this->assertMoneyValue('order_items', 'price_snapshot', '1.235');
        $this->assertMoneyValue('modifier_options', 'price_adjustment', '0.125');
        $this->assertMoneyValue('order_item_modifiers', 'price_adjustment_snapshot', '0.125');
        $this->assertMoneyValue('payments', 'amount', '1.235');
        $this->assertMoneyValue('pricing_rules', 'discount_value', '0.125');
        $this->assertMoneyValue('shift_closures', 'expected_cash', '1.235');
        $this->assertMoneyValue('shift_closures', 'actual_cash', '1.110');
        $this->assertMoneyValue('shift_closures', 'difference', '-0.125');
    }

    private function assertMoneyValue(string $table, string $column, string $expected): void
    {
        $value = DB::table($table)->value($column);

        $this->assertSame(
            $expected,
            number_format((float) $value, 3, '.', ''),
            "{$table}.{$column} did not preserve the expected 3-decimal value.",
        );
    }
}

<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\PricingRule;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PricingRuleTest extends TestCase
{
    use RefreshDatabase;

    private Shop $shop;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = Shop::factory()->create();
        $this->category = Category::factory()->create(['shop_id' => $this->shop->id]);
        $this->product = Product::factory()->create([
            'shop_id' => $this->shop->id,
            'category_id' => $this->category->id,
            'price' => 1.000,
        ]);
    }

    /**
     * Helper to create a PricingRule with sensible defaults.
     */
    private function createRule(array $overrides = []): PricingRule
    {
        return PricingRule::create(array_merge([
            'shop_id' => $this->shop->id,
            'name' => 'Test Rule',
            'discount_type' => 'percentage',
            'discount_value' => 20.0,
            'start_time' => '08:00',
            'end_time' => '22:00',
            'days_of_week' => null,
            'is_active' => true,
            'category_id' => null,
            'product_id' => null,
        ], $overrides));
    }

    public function test_active_now_scope_matches_rule_within_time_window(): void
    {
        // Rule saved with H:i format (as the admin form does)
        $this->createRule([
            'start_time' => '08:00',
            'end_time' => '22:00',
        ]);

        $now = Carbon::parse('2026-03-19 14:00:00'); // Thursday, within window

        $matched = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $this->assertCount(1, $matched);
    }

    public function test_active_now_scope_matches_with_days_of_week(): void
    {
        // Thursday = dayOfWeek 4
        $this->createRule([
            'days_of_week' => [4],
        ]);

        $thursday = Carbon::parse('2026-03-19 14:00:00'); // Thursday
        $friday = Carbon::parse('2026-03-20 14:00:00');   // Friday = 5

        $matchedThursday = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($thursday)
            ->get();

        $matchedFriday = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($friday)
            ->get();

        $this->assertCount(1, $matchedThursday, 'Should match on Thursday (day 4)');
        $this->assertCount(0, $matchedFriday, 'Should not match on Friday (day 5)');
    }

    public function test_active_now_scope_excludes_inactive_rules(): void
    {
        $this->createRule([
            'is_active' => false,
        ]);

        $now = Carbon::parse('2026-03-19 14:00:00');

        $matched = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $this->assertCount(0, $matched);
    }

    public function test_active_now_scope_excludes_outside_time_window(): void
    {
        $this->createRule([
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $tooEarly = Carbon::parse('2026-03-19 07:59:59');
        $tooLate = Carbon::parse('2026-03-19 12:00:01');
        $justRight = Carbon::parse('2026-03-19 10:00:00');

        $this->assertCount(0, PricingRule::where('shop_id', $this->shop->id)->activeNow($tooEarly)->get());
        $this->assertCount(0, PricingRule::where('shop_id', $this->shop->id)->activeNow($tooLate)->get());
        $this->assertCount(1, PricingRule::where('shop_id', $this->shop->id)->activeNow($justRight)->get());
    }

    public function test_get_time_priced_applies_product_specific_rule(): void
    {
        // 20% discount on a 1.000 OMR product = 0.800
        $this->createRule([
            'product_id' => $this->product->id,
            'discount_type' => 'percentage',
            'discount_value' => 20.0,
        ]);

        $now = Carbon::parse('2026-03-19 14:00:00');

        $rules = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $discountedPrice = $this->product->getTimePriced($rules);

        $this->assertEquals(0.800, $discountedPrice);
    }

    public function test_get_time_priced_applies_category_rule(): void
    {
        $this->createRule([
            'category_id' => $this->category->id,
            'discount_type' => 'percentage',
            'discount_value' => 10.0,
        ]);

        $now = Carbon::parse('2026-03-19 14:00:00');

        $rules = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $discountedPrice = $this->product->getTimePriced($rules);

        // 10% off 1.000 = 0.900
        $this->assertEquals(0.900, $discountedPrice);
    }

    public function test_get_time_priced_prefers_product_rule_over_category(): void
    {
        // Category rule: 10% off
        $this->createRule([
            'name' => 'Category discount',
            'category_id' => $this->category->id,
            'discount_type' => 'percentage',
            'discount_value' => 10.0,
        ]);

        // Product-specific rule: 30% off (should win)
        $this->createRule([
            'name' => 'Product discount',
            'product_id' => $this->product->id,
            'discount_type' => 'percentage',
            'discount_value' => 30.0,
        ]);

        $now = Carbon::parse('2026-03-19 14:00:00');

        $rules = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $discountedPrice = $this->product->getTimePriced($rules);

        // Product-specific rule should take precedence: 30% off 1.000 = 0.700
        $this->assertEquals(0.700, $discountedPrice);
    }

    public function test_is_active_now_instance_method(): void
    {
        $rule = $this->createRule([
            'start_time' => '08:00',
            'end_time' => '22:00',
            'days_of_week' => [4], // Thursday
        ]);

        $thursdayInWindow = Carbon::parse('2026-03-19 14:00:00');
        $thursdayOutOfWindow = Carbon::parse('2026-03-19 23:00:00');
        $fridayInWindow = Carbon::parse('2026-03-20 14:00:00');

        $this->assertTrue($rule->isActiveNow($thursdayInWindow), 'Should be active on Thursday within window');
        $this->assertFalse($rule->isActiveNow($thursdayOutOfWindow), 'Should be inactive on Thursday outside window');
        $this->assertFalse($rule->isActiveNow($fridayInWindow), 'Should be inactive on Friday');
    }

    public function test_active_now_scope_handles_string_days_in_json(): void
    {
        // Simulate data saved with string day values (e.g., from older saves)
        $rule = $this->createRule([
            'days_of_week' => [4],
        ]);

        // Manually update to string values to simulate the bug scenario
        $rule->update(['days_of_week' => ['4']]);
        $rule->refresh();

        $now = Carbon::parse('2026-03-19 14:00:00'); // Thursday

        $matched = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($now)
            ->get();

        $this->assertCount(1, $matched, 'Should match even when days_of_week contains string values');
    }

    public function test_is_active_now_handles_string_days_in_json(): void
    {
        $rule = $this->createRule([
            'days_of_week' => ['4'], // string day values
        ]);

        $thursday = Carbon::parse('2026-03-19 14:00:00');

        $this->assertTrue($rule->isActiveNow($thursday), 'isActiveNow should handle string day values');
    }

    public function test_active_now_scope_matches_at_exact_boundary_times(): void
    {
        $this->createRule([
            'start_time' => '14:00',
            'end_time' => '14:00',
        ]);

        $exactTime = Carbon::parse('2026-03-19 14:00:00');

        $matched = PricingRule::where('shop_id', $this->shop->id)
            ->activeNow($exactTime)
            ->get();

        $this->assertCount(1, $matched, 'Should match at exact start/end boundary');
    }
}

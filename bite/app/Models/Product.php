<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'price',
        'tax_rate',
        'discount_value',
        'discount_type',
        'is_on_sale',
        'image_url',
        'is_available',
        'sort_order',
        'is_visible',
    ];

    /**
     * shop_id must be set explicitly to prevent tenant isolation bypass.
     */
    protected $guarded = [
        'id',
        'shop_id',
    ];

    public function getFinalPriceAttribute()
    {
        if (! $this->is_on_sale) {
            return (float) $this->price;
        }

        if ($this->discount_type === 'percentage') {
            return round((float) ($this->price - ($this->price * ($this->discount_value / 100))), 3);
        }

        return round((float) ($this->price - $this->discount_value), 3);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group');
    }

    /**
     * Get the time-based discounted price for this product.
     *
     * Checks active pricing rules that target this product directly (by product_id)
     * or by its category (category_id). Product-specific rules take precedence.
     * Falls back to final_price if no time-based rule applies.
     *
     * Pass a pre-loaded collection of rules to avoid N+1 queries when
     * iterating over many products.
     */
    public function getTimePriced(?Collection $preloadedRules = null): float
    {
        $rules = $preloadedRules ?? PricingRule::where('shop_id', $this->shop_id)
            ->activeNow()
            ->get();

        // Product-specific rules first
        $productRule = $rules->first(fn (PricingRule $rule) => $rule->product_id === $this->id);

        if ($productRule) {
            return $productRule->applyTo($this);
        }

        // Category-level rules
        $categoryRule = $rules->first(fn (PricingRule $rule) => $rule->category_id === $this->category_id && $rule->product_id === null);

        if ($categoryRule) {
            return $categoryRule->applyTo($this);
        }

        // Global shop rules (no product_id and no category_id)
        $globalRule = $rules->first(fn (PricingRule $rule) => $rule->product_id === null && $rule->category_id === null);

        if ($globalRule) {
            return $globalRule->applyTo($this);
        }

        return $this->final_price;
    }

    /**
     * Accessor shorthand: $product->time_priced
     * Note: This queries the database each time. For bulk operations,
     * use getTimePriced($preloadedRules) instead.
     */
    public function getTimePricedAttribute(): float
    {
        return $this->getTimePriced();
    }
}

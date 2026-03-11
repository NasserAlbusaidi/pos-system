<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
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
            return (float) ($this->price - ($this->price * ($this->discount_value / 100)));
        }

        return (float) ($this->price - $this->discount_value);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group');
    }
}

<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'shop_id',
        'name_en',
        'name_ar',
        'min_selection',
        'max_selection',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function options()
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group');
    }

    public function auditSnapshot(): array
    {
        $this->loadMissing(['options', 'products']);

        return [
            'group_name' => $this->name_en,
            'group_name_ar' => $this->name_ar,
            'min_selection' => (int) $this->min_selection,
            'max_selection' => (int) $this->max_selection,
            'option_count' => $this->options->count(),
            'attached_product_count' => $this->products->count(),
            'attached_product_ids' => $this->products
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all(),
            'options' => $this->options
                ->map(fn (ModifierOption $option) => $option->auditSnapshot())
                ->values()
                ->all(),
        ];
    }
}

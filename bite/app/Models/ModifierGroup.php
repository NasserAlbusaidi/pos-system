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
}

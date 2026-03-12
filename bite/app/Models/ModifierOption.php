<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierOption extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'modifier_group_id',
        'name_en',
        'name_ar',
        'price_adjustment',
    ];

    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'name',
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

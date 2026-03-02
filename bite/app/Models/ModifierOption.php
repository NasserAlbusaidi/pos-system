<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_adjustment',
    ];

    public function group()
    {
        return $this->belongsTo(ModifierGroup::class, 'modifier_group_id');
    }
}

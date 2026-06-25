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

    public function auditSnapshot(): array
    {
        $this->loadMissing('group');

        return [
            'modifier_group_id' => (int) $this->modifier_group_id,
            'group_name' => $this->group?->name_en,
            'option_name' => $this->name_en,
            'option_name_ar' => $this->name_ar,
            'price_adjustment' => (float) $this->price_adjustment,
        ];
    }
}

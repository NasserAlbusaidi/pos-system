<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemModifier extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'order_item_id',
        'modifier_option_name_snapshot_en',
        'modifier_option_name_snapshot_ar',
        'price_adjustment_snapshot',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}

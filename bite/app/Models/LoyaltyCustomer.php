<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'phone',
        'name',
        'points',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}

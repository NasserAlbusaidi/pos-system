<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * No mass assignment — all fields must be set explicitly
     * to prevent financial manipulation.
     */
    protected $guarded = ['id'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_order_id',
        'split_group_id',
        'shop_id',
        'customer_name',
        'loyalty_phone',
        'status',
        'total_amount',
        'subtotal_amount',
        'tax_amount',
        'payment_method',
        'tracking_token',
        'fulfilled_at',
        'paid_at',
        'expires_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (blank($order->tracking_token)) {
                $order->tracking_token = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'fulfilled_at' => 'datetime',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_order_id');
    }

    public function splits()
    {
        return $this->hasMany(self::class, 'parent_order_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidTotalAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_total);
    }

    public static function cancelExpired()
    {
        return self::where('status', 'unpaid')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('payments')
            ->update(['status' => 'cancelled']);
    }
}

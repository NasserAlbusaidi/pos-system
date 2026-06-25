<?php

namespace App\Models;

use App\Support\ShopClock;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftClosure extends Model
{
    use HasFactory;

    public const PAYMENT_LOCK_MESSAGE = 'Shift is closed for today. Payments are locked until the next business day.';

    protected $guarded = ['id'];

    protected $casts = [
        'business_date' => 'date:Y-m-d',
        'expected_cash' => 'decimal:3',
        'actual_cash' => 'decimal:3',
        'difference' => 'decimal:3',
        'shift_summary' => 'array',
        'closed_at' => 'datetime',
    ];

    public static function businessDateFor(Shop $shop, ?CarbonInterface $at = null): string
    {
        return ShopClock::localDate($shop, $at);
    }

    public static function isClosedFor(Shop $shop, ?CarbonInterface $at = null): bool
    {
        return static::where('shop_id', $shop->id)
            ->where('business_date', static::businessDateFor($shop, $at))
            ->exists();
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PricingRule extends Model
{
    protected $fillable = [
        'shop_id',
        'category_id',
        'product_id',
        'name',
        'discount_type',
        'discount_value',
        'start_time',
        'end_time',
        'days_of_week',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'discount_value' => 'float',
        'is_active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to rules that are active right now:
     * - is_active = true
     * - current time falls between start_time and end_time
     * - current day of week is in days_of_week (or days_of_week is null for every day)
     */
    public function scopeActiveNow(Builder $query, ?Carbon $now = null): Builder
    {
        $now = $now ?? now();
        $currentTime = $now->format('H:i:s');
        $currentDay = (int) $now->dayOfWeek; // 0=Sunday through 6=Saturday

        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        $pad = $driver === 'sqlite'
            ? fn (string $col) => "CASE WHEN LENGTH({$col}) = 5 THEN {$col} || ':00' ELSE {$col} END"
            : fn (string $col) => "CASE WHEN LENGTH({$col}) = 5 THEN CONCAT({$col}, ':00') ELSE {$col} END";

        return $query
            ->where('is_active', true)
            ->whereRaw("{$pad('start_time')} <= ?", [$currentTime])
            ->whereRaw("{$pad('end_time')} >= ?", [$currentTime])
            ->where(function (Builder $q) use ($currentDay) {
                $q->whereNull('days_of_week')
                    ->orWhereJsonContains('days_of_week', $currentDay)
                    ->orWhereJsonContains('days_of_week', (string) $currentDay);
            });
    }

    /**
     * Check whether this rule is currently in effect.
     */
    public function isActiveNow(?Carbon $now = null): bool
    {
        $now = $now ?? now();
        $currentTime = $now->format('H:i:s');
        $currentDay = (int) $now->dayOfWeek;

        if (! $this->is_active) {
            return false;
        }

        $start = strlen($this->start_time) === 5 ? $this->start_time.':00' : $this->start_time;
        $end = strlen($this->end_time) === 5 ? $this->end_time.':00' : $this->end_time;

        if ($currentTime < $start || $currentTime > $end) {
            return false;
        }

        if ($this->days_of_week !== null) {
            $days = array_map('intval', $this->days_of_week);

            if (! in_array($currentDay, $days, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply this rule's discount to a product and return the discounted price.
     * Never returns below zero.
     */
    public function applyTo(Product $product): float
    {
        $basePrice = $product->final_price;

        if ($this->discount_type === 'percentage') {
            $discount = $basePrice * ($this->discount_value / 100);
        } else {
            $discount = $this->discount_value;
        }

        return max(0.0, round($basePrice - $discount, 3));
    }
}

<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeReportableForPaymentSummary(
        Builder $query,
        int $shopId,
        CarbonInterface $dayStartUtc,
        CarbonInterface $dayEndUtc
    ): Builder {
        return $query
            ->where('payments.shop_id', $shopId)
            ->whereBetween('payments.paid_at', [$dayStartUtc, $dayEndUtc])
            ->where(function (Builder $query) use ($shopId, $dayStartUtc, $dayEndUtc): void {
                $query
                    ->where(function (Builder $query) use ($shopId, $dayStartUtc, $dayEndUtc): void {
                        $query->whereHas('order', function (Builder $query) use ($shopId, $dayStartUtc, $dayEndUtc): void {
                            $query->where('shop_id', $shopId)
                                ->revenueRecognized()
                                ->whereBetween('paid_at', [$dayStartUtc, $dayEndUtc]);
                        });
                    })
                    ->orWhereNotNull('payments.reverses_payment_id')
                    ->orWhereExists(function ($query) use ($shopId): void {
                        $query->selectRaw('1')
                            ->from('payments as payment_reversals')
                            ->whereColumn('payment_reversals.reverses_payment_id', 'payments.id')
                            ->where('payment_reversals.shop_id', $shopId);
                    });
            });
    }

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

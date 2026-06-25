<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const REVENUE_STATUSES = ['paid', 'preparing', 'ready', 'completed'];

    /**
     * shop_id must be set explicitly (via forceCreate) to prevent tenant isolation bypass.
     * All other fields are safe for internal mass-assignment since orders are
     * never created from raw user input.
     */
    protected $fillable = [
        'parent_order_id',
        'split_group_id',
        'customer_name',
        'source',
        'loyalty_phone',
        'status',
        'total_amount',
        'subtotal_amount',
        'tax_amount',
        'payment_method',
        'tracking_token',
        'idempotency_key',
        'idempotency_fingerprint',
        'fulfilled_at',
        'paid_at',
        'expires_at',
        'customer_rating',
        'customer_feedback',
        'order_note',
    ];

    protected $guarded = [
        'id',
        'shop_id',
    ];

    /**
     * Map the internal lifecycle status to a customer-safe label for guest
     * surfaces (the public JSON API today, the tracker timeline conceptually).
     * Internal words like "unpaid" are never exposed — they read as alarming
     * to a guest who has already placed a pay-at-counter order.
     */
    public function customerStatus(): string
    {
        return match ($this->status) {
            'paid' => 'accepted',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'received', // 'unpaid' + any unknown → neutral "received"
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'counter' => __('admin.source_counter'),
            default => __('admin.source_guest'),
        };
    }

    public function scopeRevenueRecognized(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::REVENUE_STATUSES)
            ->whereNotNull('paid_at');
    }

    protected static function booted(): void
    {
        static::creating(function (self $order): void {
            if (blank($order->tracking_token)) {
                $order->tracking_token = (string) Str::uuid();
            }

            if (blank($order->source)) {
                $order->source = 'guest';
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

    public function trustedPayments(): Collection
    {
        if (! $this->exists) {
            return collect();
        }

        if ($this->relationLoaded('payments')) {
            return $this->payments
                ->where('shop_id', $this->shop_id)
                ->values();
        }

        return $this->trustedPaymentsQuery()
            ->orderBy('id')
            ->get();
    }

    public function trustedPaymentsQuery()
    {
        return $this->payments()
            ->where('shop_id', $this->shop_id);
    }

    public function getPaidTotalAttribute(): float
    {
        return round((float) $this->trustedPaymentsQuery()->sum('amount'), 3);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, round((float) $this->total_amount - $this->paid_total, 3));
    }

    public function paymentSummaryMethod(): ?string
    {
        $methods = $this->trustedPaymentsQuery()
            ->orderBy('id')
            ->pluck('method')
            ->filter(fn ($method) => is_string($method) && trim($method) !== '')
            ->values();

        if ($methods->isEmpty()) {
            return null;
        }

        return $methods->count() > 1 ? 'split' : $methods->first();
    }

    public function canCancelWithoutPaymentReversal(): bool
    {
        return $this->status === 'unpaid'
            && ! $this->trustedPaymentsQuery()->exists();
    }

    public function cancelIfExpiredUnpaid(): bool
    {
        if (
            $this->status !== 'unpaid'
            || ! $this->expires_at
            || $this->expires_at->isFuture()
            || $this->trustedPaymentsQuery()->exists()
        ) {
            return false;
        }

        $this->forceFill(['status' => 'cancelled'])->save();

        return true;
    }

    public static function cancelExpired()
    {
        return self::where('status', 'unpaid')
            ->where('expires_at', '<', now())
            ->whereDoesntHave('payments')
            ->update(['status' => 'cancelled']);
    }
}

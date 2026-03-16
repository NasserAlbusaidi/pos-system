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
        'favorites',
        'visit_count',
        'last_visit_at',
    ];

    protected $casts = [
        'favorites' => 'array',
        'last_visit_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Record a customer visit: increment visit count and update last visit timestamp.
     */
    public function recordVisit(): static
    {
        $this->visit_count = (int) ($this->visit_count ?? 0) + 1;
        $this->last_visit_at = now();
        $this->save();

        return $this;
    }

    /**
     * Save a simplified cart as the customer's favorites JSON.
     * Format: [{id, name, quantity, selectedModifiers}] — no prices stored for security.
     */
    public function saveFavorites(array $cartItems): static
    {
        $simplified = collect($cartItems)->values()->map(fn (array $item) => [
            'id' => $item['id'],
            'name' => $item['name'] ?? '',
            'quantity' => (int) ($item['quantity'] ?? 1),
            'selectedModifiers' => $item['selectedModifiers'] ?? [],
        ])->all();

        $this->favorites = $simplified;
        $this->save();

        return $this;
    }

    /**
     * Return the customer's favorites array, or empty array if none.
     */
    public function getFavorites(): array
    {
        return is_array($this->favorites) ? $this->favorites : [];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GroupCart extends Model
{
    protected $fillable = [
        'shop_id',
        'group_token',
        'items',
        'participant_count',
        'expires_at',
    ];

    protected $casts = [
        'items' => 'array',
        'expires_at' => 'datetime',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Add an item to the group cart for a specific participant.
     * Uses DB transaction + row lock to prevent race conditions.
     */
    public function addItem(string $participantId, array $item): self
    {
        return DB::transaction(function () use ($participantId, $item) {
            self::where('id', $this->id)->lockForUpdate()->first();
            $this->refresh();
            $items = $this->items ?? [];

            $itemKey = $item['itemKey'] ?? null;

            // Check if this participant already has the same product+modifiers combo
            $existingIndex = null;
            if ($itemKey) {
                foreach ($items as $index => $existing) {
                    if (($existing['participant_id'] ?? '') === $participantId
                        && ($existing['itemKey'] ?? '') === $itemKey) {
                        $existingIndex = $index;
                        break;
                    }
                }
            }

            if ($existingIndex !== null) {
                // Increment quantity on existing item (immutable: build new array)
                $updated = $items[$existingIndex];
                $updated['quantity'] = ($updated['quantity'] ?? 1) + ($item['quantity'] ?? 1);
                $items[$existingIndex] = $updated;
            } else {
                $items[] = array_merge($item, [
                    'participant_id' => $participantId,
                ]);
            }

            $this->items = $items;
            $this->save();

            return $this;
        });
    }

    /**
     * Remove an item from the group cart by participant and item key.
     * Uses DB transaction + row lock to prevent race conditions.
     */
    public function removeItem(string $participantId, string $itemKey): self
    {
        return DB::transaction(function () use ($participantId, $itemKey) {
            self::where('id', $this->id)->lockForUpdate()->first();
            $this->refresh();
            $items = collect($this->items ?? [])
                ->reject(fn (array $entry) => ($entry['participant_id'] ?? '') === $participantId
                    && ($entry['itemKey'] ?? '') === $itemKey)
                ->values()
                ->all();

            $this->items = $items;
            $this->save();

            return $this;
        });
    }

    /**
     * Update the quantity of an item. If quantity reaches 0, remove it.
     * Uses DB transaction + row lock to prevent race conditions.
     */
    public function updateItemQuantity(string $participantId, string $itemKey, int $delta): self
    {
        return DB::transaction(function () use ($participantId, $itemKey, $delta) {
            self::where('id', $this->id)->lockForUpdate()->first();
            $this->refresh();
            $items = $this->items ?? [];
            $updated = [];

            foreach ($items as $entry) {
                if (($entry['participant_id'] ?? '') === $participantId
                    && ($entry['itemKey'] ?? '') === $itemKey) {
                    $entry['quantity'] = max(0, ($entry['quantity'] ?? 1) + $delta);
                    if ($entry['quantity'] <= 0) {
                        continue; // drop this item
                    }
                }
                $updated[] = $entry;
            }

            $this->items = $updated;
            $this->save();

            return $this;
        });
    }

    /**
     * Get items for a specific participant.
     */
    public function getItemsForParticipant(string $participantId): array
    {
        return collect($this->items ?? [])
            ->filter(fn (array $entry) => ($entry['participant_id'] ?? '') === $participantId)
            ->values()
            ->all();
    }

    /**
     * Check if this group cart has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Delete all expired group carts (older than 1 hour).
     */
    public static function cleanExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }
}

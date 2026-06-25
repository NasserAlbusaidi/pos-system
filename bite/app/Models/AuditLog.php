<?php

namespace App\Models;

use App\Support\PiiMasker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function displayMeta(): array
    {
        return self::maskSensitiveMetaForDisplay($this->meta ?? []);
    }

    public static function maskSensitiveMetaForDisplay(array $meta): array
    {
        $masked = [];

        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $masked[$key] = self::maskSensitiveMetaForDisplay($value);

                continue;
            }

            $maskType = is_string($key) ? self::maskTypeForMetaKey($key) : null;
            $masked[$key] = $maskType ? self::maskMetaValue($maskType, $value) : $value;
        }

        return $masked;
    }

    private static function maskTypeForMetaKey(string $key): ?string
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        if ($normalized === 'ip' || str_ends_with($normalized, '_ip') || str_contains($normalized, 'ip_address')) {
            return 'ip';
        }

        if (str_contains($normalized, 'email')) {
            return 'email';
        }

        if (str_contains($normalized, 'phone') || str_contains($normalized, 'mobile') || str_contains($normalized, 'whatsapp')) {
            return 'phone';
        }

        return null;
    }

    private static function maskMetaValue(string $maskType, mixed $value): mixed
    {
        if ($value === null || ! is_scalar($value)) {
            return $value;
        }

        $value = (string) $value;

        return match ($maskType) {
            'email' => PiiMasker::email($value),
            'ip' => PiiMasker::ip($value),
            'phone' => PiiMasker::phone($value),
            default => $value,
        };
    }

    public static function record(string $action, ?Model $auditable = null, array $meta = []): void
    {
        $user = Auth::user();

        self::create([
            'shop_id' => $user?->shop_id ?? ($auditable?->shop_id ?? null),
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->id,
            'meta' => $meta,
        ]);
    }
}

<?php

namespace App\Http\Requests\Api\Guest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the untrusted cart for the public quote endpoint. Shape only — the
 * pricing/availability/cap integrity checks live in GuestOrderService, which
 * reprices everything server-side. An empty cart is allowed (it quotes to zero).
 */
class QuoteOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'shop' => ['required', 'string', 'max:255'],
            'cart' => ['present', 'array', 'max:200'],
            'cart.*.id' => ['required', 'integer'],
            'cart.*.quantity' => ['required', 'integer', 'min:1'],
            'cart.*.name' => ['nullable', 'string', 'max:255'],
            'cart.*.selectedModifiers' => ['nullable', 'array'],
            'cart.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api\Guest;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the untrusted cart + contact details for the public order-create
 * endpoint. Shape only — GuestOrderService owns repricing, modifier validation,
 * caps, phone normalization and idempotency. Public API clients must send an
 * idempotency_key so network retries replay the same order rather than
 * duplicating it.
 */
class StoreOrderRequest extends FormRequest
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
            'cart' => ['required', 'array', 'min:1', 'max:200'],
            'cart.*.id' => ['required', 'integer'],
            'cart.*.quantity' => ['required', 'integer', 'min:1'],
            'cart.*.name' => ['nullable', 'string', 'max:255'],
            'cart.*.selectedModifiers' => ['nullable', 'array'],
            'cart.*.note' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'loyalty_phone' => ['required', 'string', 'max:30'],
            'order_note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    /**
     * The context array GuestOrderService::create() expects.
     *
     * @return array<string, mixed>
     */
    public function orderContext(): array
    {
        return [
            'idempotency_key' => $this->validated('idempotency_key'),
            'customer_name' => $this->validated('customer_name'),
            'loyalty_phone' => $this->validated('loyalty_phone'),
            'order_note' => $this->validated('order_note'),
        ];
    }
}

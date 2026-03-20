<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\CancelOrderDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for cancelling an order.
 *
 * A cancellation reason is mandatory so every cancellation can be
 * audited and communicated to the customer.
 */
class CancelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for cancelling an order.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * Build a CancelOrderDto from the validated request data.
     *
     * @return CancelOrderDto
     */
    public function dto(): CancelOrderDto
    {
        return CancelOrderDto::fromArray($this->validated());
    }
}

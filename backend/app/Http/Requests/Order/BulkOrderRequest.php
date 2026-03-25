<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\BulkOrderDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the payload for bulk order operations.
 *
 * Requires a non-empty array of order UUIDs. Specific bulk operations
 * (confirm, cancel, ship) may add additional rules in their own request
 * classes, but this base class ensures the order_ids are always valid.
 */
class BulkOrderRequest extends ApiRequest
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
     * Get the validation rules for a bulk order operation.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'order_ids'   => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'uuid'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): BulkOrderDto
    {
        return BulkOrderDto::fromArray($this->validated());
    }

}

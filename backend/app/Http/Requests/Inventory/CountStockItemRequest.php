<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Domain\Inventory\Data\CountStockItemDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the request payload for recording a physically counted quantity on a stock count item.
 */
class CountStockItemRequest extends ApiRequest
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
     * Get the validation rules for counting a stock item.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'quantity_counted' => ['required', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): CountStockItemDto
    {
        return CountStockItemDto::fromArray($this->validated());
    }

}

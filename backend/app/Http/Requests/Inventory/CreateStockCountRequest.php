<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the request payload for creating a new stock count task.
 */
class CreateStockCountRequest extends FormRequest
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
     * Get the validation rules for creating a stock count.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id'   => ['required', 'uuid'],
            'type'           => ['required', 'string', 'in:full,partial,cycle_count,spot_check'],
            'scheduled_at'   => ['nullable', 'date'],
            'notes'          => ['nullable', 'string'],
            'variant_ids'    => ['nullable', 'array'],
            'variant_ids.*'  => ['uuid'],
        ];
    }

    /**
     * Build a CreateStockCountDto from the validated request data.
     *
     * @return \App\Domain\Inventory\Data\CreateStockCountDto
     */
    public function dto(): \App\Domain\Inventory\Data\CreateStockCountDto
    {
        return new \App\Domain\Inventory\Data\CreateStockCountDto(
            warehouseId:  $this->input('warehouse_id'),
            type:         $this->input('type'),
            createdBy:    $this->user()->id,
            scheduledAt:  $this->input('scheduled_at'),
            notes:        $this->input('notes'),
            variantIds:   $this->input('variant_ids', []),
        );
    }
}

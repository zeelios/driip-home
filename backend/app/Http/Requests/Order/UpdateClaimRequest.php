<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\Domain\Order\Data\UpdateClaimDto;

use App\Http\Requests\ApiRequest;

/**
 * Validates the payload for updating an existing order claim.
 *
 * Allows updating status, resolution details, refund amount, and
 * the assigned handler. All fields are optional for partial updates.
 */
class UpdateClaimRequest extends ApiRequest
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
     * Get the validation rules for updating a claim.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'status'           => ['sometimes', 'in:open,investigating,resolved,rejected'],
            'resolution'       => ['nullable', 'string'],
            'resolution_notes' => ['nullable', 'string'],
            'refund_amount'    => ['nullable', 'integer', 'min:0'],
            'assigned_to'      => ['nullable', 'uuid'],
        ];
    }
    /**
     * Build the DTO from validated request data.
     */
    public function dto(): UpdateClaimDto
    {
        return UpdateClaimDto::fromArray($this->validated());
    }

}

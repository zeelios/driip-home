<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the payload for creating a new order claim.
 *
 * Claims may be filed against the entire order or a specific line item.
 * Evidence URLs are optional and stored as a JSON array.
 */
class CreateClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled at the controller/policy layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for creating a claim.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'type'          => ['required', 'in:wrong_item,damaged,missing_item,late_delivery,quality_issue,other'],
            'description'   => ['required', 'string'],
            'evidence_urls' => ['nullable', 'array'],
            'evidence_urls.*' => ['url'],
            'order_item_id' => ['nullable', 'uuid'],
        ];
    }
}

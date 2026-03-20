<?php

declare(strict_types=1);

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource transforming a CustomerInteraction model into a JSON-serializable array.
 *
 * Includes all interaction fields and, when the createdBy relation is loaded,
 * the name of the staff member who logged the interaction.
 *
 * @mixin \App\Domain\Customer\Models\CustomerInteraction
 */
class CustomerInteractionResource extends JsonResource
{
    /**
     * Transform the interaction into an array for API responses.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'customer_id'  => $this->customer_id,
            'type'         => $this->type,
            'channel'      => $this->channel,
            'summary'      => $this->summary,
            'outcome'      => $this->outcome,
            'follow_up_at' => $this->follow_up_at?->toIso8601String(),
            'created_by'   => $this->created_by,
            'created_by_name' => $this->whenLoaded(
                'createdBy',
                fn () => $this->createdBy?->name,
            ),
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single warehouse.
 *
 * @mixin \App\Domain\Warehouse\Models\Warehouse
 */
class WarehouseResource extends JsonResource
{
    /**
     * Transform the warehouse into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'code'     => $this->code,
            'name'     => $this->name,
            'type'     => $this->type,
            'address'  => $this->address,
            'province' => $this->province,
            'district' => $this->district,
            'phone'    => $this->phone,
            'is_active' => $this->is_active,
            'notes'    => $this->notes,

            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id'            => $this->manager->id,
                'name'          => $this->manager->name,
                'employee_code' => $this->manager->employee_code,
            ] : null),

            'staff_assignments' => $this->whenLoaded(
                'staffAssignments',
                fn () => $this->staffAssignments->map(fn ($assignment) => [
                    'id'            => $assignment->id,
                    'user_id'       => $assignment->user_id,
                    'role'          => $assignment->role,
                    'assigned_at'   => $assignment->assigned_at?->toDateString(),
                    'unassigned_at' => $assignment->unassigned_at?->toDateString(),
                ])
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

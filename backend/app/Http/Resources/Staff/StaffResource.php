<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single staff member.
 *
 * @mixin \App\Domain\Staff\Models\User
 */
class StaffResource extends JsonResource
{
    /**
     * Transform the staff member into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'employee_code' => $this->employee_code,
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'department'    => $this->department,
            'position'      => $this->position,
            'status'        => $this->status,
            'avatar'        => $this->avatar,
            'hired_at'      => $this->hired_at?->toDateString(),
            'roles'         => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for a single salary record.
 *
 * Exposes all salary fields including allowances, bonuses, deductions,
 * overtime figures, and payment information.
 *
 * @mixin \App\Domain\Staff\Models\SalaryRecord
 */
class SalaryRecordResource extends JsonResource
{
    /**
     * Transform the salary record into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'period'             => $this->period,
            'base_salary'        => $this->base_salary,
            'allowances'         => $this->allowances ?? [],
            'overtime_hours'     => $this->overtime_hours,
            'overtime_rate'      => $this->overtime_rate,
            'bonuses'            => $this->bonuses ?? [],
            'deductions'         => $this->deductions ?? [],
            'total_gross'        => $this->total_gross,
            'total_net'          => $this->total_net,
            'paid_at'            => $this->paid_at?->toIso8601String(),
            'payment_method'     => $this->payment_method,
            'payment_reference'  => $this->payment_reference,
            'notes'              => $this->notes,
            'created_by'         => $this->created_by,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}

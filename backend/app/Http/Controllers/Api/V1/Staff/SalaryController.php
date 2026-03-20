<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Staff;

use App\Domain\Staff\Models\SalaryRecord;
use App\Domain\Staff\Models\User;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Staff\CreateSalaryRequest;
use App\Http\Resources\Staff\SalaryRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage salary records for individual staff members.
 *
 * Handles listing and creating salary records scoped to a given user.
 * All responses use SalaryRecordResource for consistent API shape.
 */
class SalaryController extends BaseApiController
{
    /**
     * List salary records for a given staff member.
     *
     * Supports filtering by period and payment_method, and sorting
     * by period or paid_at via query builder.
     *
     * @permission manage-staff
     *
     * @param  Request  $request
     * @param  User     $staff   The staff member whose records to list.
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, User $staff): AnonymousResourceCollection
    {
        $records = QueryBuilder::for(
            SalaryRecord::where('user_id', $staff->id)
        )
            ->allowedFilters('period', 'payment_method')
            ->allowedSorts('period', 'paid_at', 'created_at')
            ->paginate($request->integer('per_page', 20));

        return SalaryRecordResource::collection($records);
    }

    /**
     * Create a new salary record for the given staff member.
     *
     * Automatically sets the created_by field to the authenticated user's ID.
     *
     * @permission manage-staff
     *
     * @param  CreateSalaryRequest  $request
     * @param  User                 $staff   The staff member to create the record for.
     * @return SalaryRecordResource|JsonResponse
     */
    public function store(CreateSalaryRequest $request, User $staff): SalaryRecordResource|JsonResponse
    {
        try {
            $data     = $request->validated();
            $authUser = $request->user();

            $allowances  = $data['allowances'] ?? [];
            $bonuses     = $data['bonuses'] ?? [];
            $deductions  = $data['deductions'] ?? [];
            $baseSalary  = (int) $data['base_salary'];
            $overtimePay = (int) (($data['overtime_hours'] ?? 0) * ($data['overtime_rate'] ?? 0));

            $totalGross = $baseSalary
                + $overtimePay
                + (int) array_sum($allowances)
                + (int) array_sum($bonuses);

            $totalNet = $totalGross - (int) array_sum($deductions);

            $record = SalaryRecord::create([
                'user_id'           => $staff->id,
                'period'            => $data['period'],
                'base_salary'       => $baseSalary,
                'allowances'        => $allowances,
                'overtime_hours'    => $data['overtime_hours'] ?? 0,
                'overtime_rate'     => $data['overtime_rate'] ?? 0,
                'bonuses'           => $bonuses,
                'deductions'        => $deductions,
                'total_gross'       => $totalGross,
                'total_net'         => $totalNet,
                'paid_at'           => $data['paid_at'] ?? null,
                'payment_method'    => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'notes'             => $data['notes'] ?? null,
                'created_by'        => $authUser?->id,
            ]);

            return SalaryRecordResource::make($record);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_SALARY');
        }
    }
}

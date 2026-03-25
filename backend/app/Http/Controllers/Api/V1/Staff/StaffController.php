<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Staff;

use App\Domain\Staff\Actions\CreateStaffAction;
use App\Domain\Staff\Actions\UpdateStaffAction;
use App\Domain\Staff\Exceptions\StaffEmailAlreadyExistsException;
use App\Domain\Staff\Models\User;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Middleware\RoleHierarchyMiddleware;
use App\Http\Requests\Staff\CreateStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\Staff\StaffResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Manage staff members (employees) of Driip.
 *
 * Handles listing, creating, showing, updating, and soft-deleting staff.
 * All operations delegate to dedicated Action classes.
 */
class StaffController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:staff.view', only: ['index', 'show']),
            new Middleware('permission:staff.create', only: ['store']),
            new Middleware('permission:staff.update', only: ['update']),
            new Middleware(RoleHierarchyMiddleware::class, only: ['update', 'destroy']),
            new Middleware('permission:staff.delete', only: ['destroy']),
        ];
    }

    /**
     * @param CreateStaffAction $createStaff Action responsible for creating a new staff member.
     * @param UpdateStaffAction $updateStaff Action responsible for updating a staff member.
     */
    public function __construct(
        private readonly CreateStaffAction $createStaff,
        private readonly UpdateStaffAction $updateStaff,
    ) {
    }

    /**
     * List all staff members with filtering and sorting.
     *
     * @permission manage-staff
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $staff = QueryBuilder::for(User::class)
            ->allowedFilters('name', 'email', 'department', 'status')
            ->allowedSorts('name', 'created_at', 'hired_at')
            ->with(['roles'])
            ->paginate($request->integer('per_page', 20));

        return StaffResource::collection($staff);
    }

    /**
     * Create a new staff member.
     *
     * @permission manage-staff
     *
     * @param  CreateStaffRequest  $request
     * @return StaffResource|JsonResponse
     */
    public function store(CreateStaffRequest $request): StaffResource|JsonResponse
    {
        try {
            $user = $this->createStaff->execute($request->dto());

            return StaffResource::make($user->load('roles'));
        } catch (StaffEmailAlreadyExistsException $e) {
            return $this->validationError(
                ValidationException::withMessages(['email' => [$e->getMessage()]]),
                'CREATE_STAFF'
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_STAFF');
        }
    }

    /**
     * Show a single staff member.
     *
     * @param  User  $staff
     * @return StaffResource
     */
    public function show(User $staff): StaffResource
    {
        return StaffResource::make($staff->load(['roles', 'profile']));
    }

    /**
     * Update an existing staff member.
     *
     * @permission manage-staff
     *
     * @param  UpdateStaffRequest  $request
     * @param  User                $staff
     * @return StaffResource|JsonResponse
     */
    public function update(UpdateStaffRequest $request, User $staff): StaffResource|JsonResponse
    {
        try {
            $updated = $this->updateStaff->execute($request->dto(), $staff);

            return StaffResource::make($updated->load('roles'));
        } catch (StaffEmailAlreadyExistsException $e) {
            return $this->validationError(
                ValidationException::withMessages(['email' => [$e->getMessage()]]),
                'UPDATE_STAFF'
            );
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_STAFF');
        }
    }

    /**
     * Soft-delete (terminate) a staff member.
     *
     * Sets the status to 'terminated', records the termination date,
     * then soft-deletes the record.
     *
     * @permission manage-staff
     *
     * @param  User  $staff
     * @return JsonResponse
     */
    public function destroy(User $staff): JsonResponse
    {
        try {
            $staff->update(['status' => 'terminated', 'terminated_at' => now()]);
            $staff->delete();

            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_STAFF');
        }
    }
}

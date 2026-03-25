<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Coupon;

use App\Domain\Coupon\Actions\CreateCouponAction;
use App\Domain\Coupon\Actions\UpdateCouponAction;
use App\Domain\Coupon\Actions\ValidateCouponAction;
use App\Domain\Coupon\Models\Coupon;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Coupon\CreateCouponRequest;
use App\Http\Requests\Coupon\UpdateCouponRequest;
use App\Http\Requests\Coupon\ValidateCouponRequest;
use App\Http\Resources\Coupon\CouponResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for coupon management and real-time validation.
 *
 * Provides standard CRUD for the back-office, and a dedicated /validate
 * endpoint used by the checkout flow to check eligibility before order
 * confirmation.
 */
class CouponController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:coupons.view', only: ['index', 'show']),
            new Middleware('permission:coupons.create', only: ['store']),
            new Middleware('permission:coupons.update', only: ['update']),
            new Middleware('permission:coupons.delete', only: ['destroy']),
            new Middleware('permission:coupons.validate', only: ['validate']),
        ];
    }

    /**
     * @param  CreateCouponAction   $createAction   Action for creating coupons.
     * @param  UpdateCouponAction   $updateAction   Action for updating coupons.
     */
    public function __construct(
        private readonly CreateCouponAction $createAction,
        private readonly UpdateCouponAction $updateAction,
    ) {
    }

    /**
     * List all coupons with optional filtering and pagination.
     *
     * Allowed filters: code, type, is_active, is_public, applies_to.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $coupons = QueryBuilder::for(Coupon::class)
                ->allowedFilters(
                    AllowedFilter::partial('code'),
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('type'),
                    AllowedFilter::exact('is_active'),
                    AllowedFilter::exact('is_public'),
                    AllowedFilter::exact('applies_to'),
                )
                ->allowedSorts('code', 'name', 'used_count', 'expires_at', 'created_at')
                ->paginate(20);

            return CouponResource::collection($coupons);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_COUPONS');
        }
    }

    /**
     * Create a new coupon via CreateCouponAction.
     *
     * @param  CreateCouponRequest  $request
     * @return CouponResource|JsonResponse
     */
    public function store(CreateCouponRequest $request): CouponResource|JsonResponse
    {
        try {
            $coupon = $this->createAction->execute($request->dto());

            return new CouponResource($coupon);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'CREATE_COUPON');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_COUPON');
        }
    }

    /**
     * Retrieve a single coupon by UUID.
     *
     * @param  Coupon  $coupon
     * @return CouponResource|JsonResponse
     */
    public function show(Coupon $coupon): CouponResource|JsonResponse
    {
        try {
            return new CouponResource($coupon);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_COUPON');
        }
    }

    /**
     * Update an existing coupon via UpdateCouponAction.
     *
     * @param  UpdateCouponRequest  $request
     * @param  Coupon               $coupon
     * @return CouponResource|JsonResponse
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon): CouponResource|JsonResponse
    {
        try {
            $updated = $this->updateAction->execute($request->dto(), $coupon);

            return new CouponResource($updated);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'UPDATE_COUPON');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_COUPON');
        }
    }

    /**
     * Soft-delete a coupon.
     *
     * @param  Coupon  $coupon
     * @return JsonResponse
     */
    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $coupon->delete();

            return response()->json(['success' => true, 'message' => 'Coupon deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_COUPON');
        }
    }

    /**
     * Validate a coupon code against the provided order context.
     *
     * Returns whether the coupon is valid, the applicable discount amount,
     * and a human-readable message explaining the result.
     *
     * @param  ValidateCouponRequest  $request
     * @param  ValidateCouponAction   $action
     * @return JsonResponse
     */
    public function validate(ValidateCouponRequest $request, ValidateCouponAction $action): JsonResponse
    {
        try {
            $result = $action->execute($request->dto());

            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => $result['valid'],
                    'discount_amount' => $result['discount_amount'],
                    'message' => $result['message'],
                    'coupon' => $result['coupon'] !== null
                        ? new CouponResource($result['coupon'])
                        : null,
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'VALIDATE_COUPON');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'VALIDATE_COUPON');
        }
    }
}

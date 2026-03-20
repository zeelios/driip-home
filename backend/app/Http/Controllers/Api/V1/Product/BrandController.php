<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Domain\Product\Models\Brand;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Product\BrandResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for brand CRUD operations.
 *
 * Provides paginated listing with query filters, and standard
 * create / read / update / delete endpoints.
 */
class BrandController extends BaseApiController
{
    /**
     * List all brands with optional filtering.
     *
     * Allowed filters: name, is_active.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $brands = QueryBuilder::for(Brand::class)
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('is_active'),
                )
                ->allowedSorts('name', 'sort_order', 'created_at')
                ->paginate(20);

            return BrandResource::collection($brands);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_BRANDS');
        }
    }

    /**
     * Create a new brand.
     *
     * @param  Request  $request
     * @return BrandResource|JsonResponse
     */
    public function store(Request $request): BrandResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => ['required', 'string', 'max:255'],
                'slug'        => ['required', 'string', 'max:255', 'unique:brands,slug'],
                'logo'        => ['nullable', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'is_active'   => ['nullable', 'boolean'],
                'sort_order'  => ['nullable', 'integer'],
            ]);

            $brand = Brand::create($validated);

            return new BrandResource($brand);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_BRAND');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_BRAND');
        }
    }

    /**
     * Retrieve a single brand by its UUID.
     *
     * @param  Brand  $brand
     * @return BrandResource|JsonResponse
     */
    public function show(Brand $brand): BrandResource|JsonResponse
    {
        try {
            return new BrandResource($brand);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_BRAND');
        }
    }

    /**
     * Update an existing brand.
     *
     * @param  Request  $request
     * @param  Brand    $brand
     * @return BrandResource|JsonResponse
     */
    public function update(Request $request, Brand $brand): BrandResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => ['sometimes', 'string', 'max:255'],
                'slug'        => ['sometimes', 'string', 'max:255', 'unique:brands,slug,' . $brand->id],
                'logo'        => ['nullable', 'string', 'max:500'],
                'description' => ['nullable', 'string'],
                'is_active'   => ['nullable', 'boolean'],
                'sort_order'  => ['nullable', 'integer'],
            ]);

            $brand->update($validated);

            return new BrandResource($brand->fresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_BRAND');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_BRAND');
        }
    }

    /**
     * Soft-delete a brand.
     *
     * @param  Brand  $brand
     * @return JsonResponse
     */
    public function destroy(Brand $brand): JsonResponse
    {
        try {
            $brand->delete();

            return response()->json(['success' => true, 'message' => 'Brand deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_BRAND');
        }
    }
}

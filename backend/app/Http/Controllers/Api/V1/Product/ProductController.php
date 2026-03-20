<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Domain\Product\Actions\CreateProductAction;
use App\Domain\Product\Models\Product;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for product CRUD operations.
 *
 * List endpoint supports rich filtering via Spatie QueryBuilder.
 * Store delegates to CreateProductAction to enforce slug uniqueness.
 * Show eagerly loads brand, category, and variants.
 */
class ProductController extends BaseApiController
{
    /**
     * List products with optional filtering and pagination.
     *
     * Allowed filters: name, brand_id, category_id, status, gender, season, is_featured.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $products = QueryBuilder::for(Product::class)
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('brand_id'),
                    AllowedFilter::exact('category_id'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('gender'),
                    AllowedFilter::exact('season'),
                    AllowedFilter::exact('is_featured'),
                )
                ->allowedSorts('name', 'created_at', 'published_at', 'status')
                ->with(['brand', 'category'])
                ->paginate(20);

            return ProductResource::collection($products);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_PRODUCTS');
        }
    }

    /**
     * Create a new product via CreateProductAction.
     *
     * @param  CreateProductRequest  $request
     * @param  CreateProductAction   $action
     * @return ProductResource|JsonResponse
     */
    public function store(CreateProductRequest $request, CreateProductAction $action): ProductResource|JsonResponse
    {
        try {
            $product = $action->execute($request->dto());

            return new ProductResource($product);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_PRODUCT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_PRODUCT');
        }
    }

    /**
     * Retrieve a single product by UUID, eager-loading brand, category, and variants.
     *
     * @param  Product  $product
     * @return ProductResource|JsonResponse
     */
    public function show(Product $product): ProductResource|JsonResponse
    {
        try {
            $product->load(['brand', 'category', 'variants']);

            return new ProductResource($product);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_PRODUCT');
        }
    }

    /**
     * Update an existing product's fields directly.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return ProductResource|JsonResponse
     */
    public function update(Request $request, Product $product): ProductResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'brand_id'          => ['nullable', 'uuid', 'exists:brands,id'],
                'category_id'       => ['nullable', 'uuid', 'exists:categories,id'],
                'name'              => ['sometimes', 'string', 'max:255'],
                'slug'              => ['sometimes', 'string', 'max:255', 'unique:products,slug,' . $product->id],
                'description'       => ['nullable', 'string'],
                'short_description' => ['nullable', 'string', 'max:500'],
                'sku_base'          => ['nullable', 'string', 'max:50'],
                'gender'            => ['nullable', 'in:men,women,unisex,kids'],
                'season'            => ['nullable', 'string', 'max:20'],
                'tags'              => ['nullable', 'array'],
                'tags.*'            => ['string'],
                'status'            => ['nullable', 'in:draft,active,archived'],
                'is_featured'       => ['nullable', 'boolean'],
                'published_at'      => ['nullable', 'date'],
                'meta_title'        => ['nullable', 'string', 'max:255'],
                'meta_description'  => ['nullable', 'string'],
            ]);

            $product->update($validated);

            return new ProductResource($product->fresh()->load(['brand', 'category']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_PRODUCT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_PRODUCT');
        }
    }

    /**
     * Soft-delete a product.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $product->delete();

            return response()->json(['success' => true, 'message' => 'Product deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_PRODUCT');
        }
    }
}

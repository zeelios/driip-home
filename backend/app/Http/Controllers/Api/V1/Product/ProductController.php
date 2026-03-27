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
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for product CRUD operations.
 *
 * List endpoint supports rich filtering via Spatie QueryBuilder.
 * Store delegates to CreateProductAction to enforce slug uniqueness.
 * Show eagerly loads brand, category, and variants.
 */
class ProductController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index', 'show', 'search']),
            new Middleware('permission:products.create', only: ['store']),
            new Middleware('permission:products.update', only: ['update']),
            new Middleware('permission:products.delete', only: ['destroy']),
        ];
    }

    /**
     * Search product variants by name or SKU for order creation.
     *
     * Returns active variants with stock info for quick selection.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = trim((string) $request->input('q', ''));
            $limit = min((int) $request->input('limit', 10), 50);

            if ($query === '') {
                return response()->json(['data' => []]);
            }

            $products = Product::search($query)
                ->take($limit)
                ->get()
                ->loadMissing(['brand', 'category', 'variantPeers', 'parentVariants'])
                ->filter(static fn(Product $product): bool => $product->status === 'active')
                ->values();

            $results = $products->map(function (Product $product): array {
                $pricing = [
                    'compare_price' => $product->compare_price,
                    'cost_price' => $product->cost_price,
                    'selling_price' => $product->selling_price,
                    'sale_price' => $product->sale_price,
                    'effective_price' => $product->effectivePrice(),
                    'currency' => 'VND',
                ];

                $variantOptions = $product->allVariants()
                    ->filter(static fn(Product $variant): bool => $variant->status === 'active')
                    ->values()
                    ->map(function (Product $variant): array {
                        $variantPricing = [
                            'compare_price' => $variant->compare_price,
                            'cost_price' => $variant->cost_price,
                            'selling_price' => $variant->selling_price,
                            'sale_price' => $variant->sale_price,
                            'effective_price' => $variant->effectivePrice(),
                            'currency' => 'VND',
                        ];

                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'sku' => $variant->sku,
                            'pricing' => $variantPricing,
                            'compare_price' => $variantPricing['compare_price'],
                            'cost_price' => $variantPricing['cost_price'],
                            'selling_price' => $variantPricing['selling_price'],
                            'sale_price' => $variantPricing['sale_price'],
                            'effective_price' => $variantPricing['effective_price'],
                            'brand' => $variant->relationLoaded('brand') && $variant->brand !== null ? [
                                'id' => $variant->brand->id,
                                'name' => $variant->brand->name,
                                'slug' => $variant->brand->slug,
                            ] : null,
                            'category' => $variant->relationLoaded('category') && $variant->category !== null ? [
                                'id' => $variant->category->id,
                                'name' => $variant->category->name,
                                'slug' => $variant->category->slug,
                            ] : null,
                        ];
                    });

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku_base' => $product->sku,
                    'pricing' => $pricing,
                    'compare_price' => $pricing['compare_price'],
                    'cost_price' => $pricing['cost_price'],
                    'selling_price' => $pricing['selling_price'],
                    'sale_price' => $pricing['sale_price'],
                    'effective_price' => $pricing['effective_price'],
                    'brand' => $product->relationLoaded('brand') && $product->brand !== null ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'slug' => $product->brand->slug,
                    ] : null,
                    'category' => $product->relationLoaded('category') && $product->category !== null ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug,
                    ] : null,
                    'gender' => $product->gender,
                    'season' => $product->season,
                    'tags' => $product->tags,
                    'status' => $product->status,
                    'is_featured' => $product->is_featured,
                    'published_at' => $product->published_at?->toIso8601String(),
                    'short_description' => $product->short_description,
                    'description' => $product->description,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'variant_options' => $variantOptions,
                ];
            });

            return response()->json([
                'data' => $results,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SEARCH_PRODUCTS');
        }
    }

    /**
     * Format variant attribute values into a readable name.
     *
     * @param  array|null  $attributeValues
     * @return string|null
     */
    private function formatVariantName(?array $attributeValues): ?string
    {
        if (empty($attributeValues)) {
            return null;
        }

        $parts = [];
        foreach ($attributeValues as $attr => $value) {
            $parts[] = "{$attr}: {$value}";
        }

        return implode(', ', $parts);
    }

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
            $product->load(['brand', 'category']);
            $product->setRelation('variants', $product->variants());

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
                'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
                'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
                'name' => ['sometimes', 'string', 'max:255'],
                'slug' => ['sometimes', 'string', 'max:255', 'unique:products,slug,' . $product->id],
                'description' => ['nullable', 'string'],
                'short_description' => ['nullable', 'string', 'max:500'],
                'sku_base' => ['nullable', 'string', 'max:50'],
                'gender' => ['nullable', 'in:men,women,unisex,kids'],
                'season' => ['nullable', 'string', 'max:20'],
                'tags' => ['nullable', 'array'],
                'tags.*' => ['string'],
                'status' => ['nullable', 'in:draft,active,archived'],
                'is_featured' => ['nullable', 'boolean'],
                'published_at' => ['nullable', 'date'],
                'meta_title' => ['nullable', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string'],
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

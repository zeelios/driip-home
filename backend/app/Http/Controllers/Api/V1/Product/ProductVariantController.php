<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Domain\Product\Actions\CreateVariantAction;
use App\Domain\Product\Actions\UpdateVariantPriceAction;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Product\CreateVariantRequest;
use App\Http\Resources\Product\ProductVariantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for product variant CRUD operations.
 *
 * Nested under /products/{product}/variants. All operations scope
 * results to the parent product via route model binding.
 *
 * An additional GET /products/{product}/variants/{variant}/inventory
 * endpoint aggregates stock quantities across all warehouses.
 */
class ProductVariantController extends BaseApiController
{
    /**
     * List all variants for a given product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request, Product $product): AnonymousResourceCollection|JsonResponse
    {
        try {
            $variants = QueryBuilder::for(
                ProductVariant::where('product_id', $product->id)
            )
                ->allowedFilters('status')
                ->allowedSorts('sku', 'sort_order', 'created_at')
                ->paginate(50);

            return ProductVariantResource::collection($variants);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_VARIANTS');
        }
    }

    /**
     * Create a new variant for a product using CreateVariantAction.
     *
     * @param  CreateVariantRequest  $request
     * @param  Product               $product
     * @param  CreateVariantAction   $action
     * @return ProductVariantResource|JsonResponse
     */
    public function store(
        CreateVariantRequest $request,
        Product              $product,
        CreateVariantAction  $action,
    ): ProductVariantResource|JsonResponse {
        try {
            $variant = $action->execute($request->dto());

            return new ProductVariantResource($variant);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_VARIANT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_VARIANT');
        }
    }

    /**
     * Retrieve a single variant, eager-loading inventory across all warehouses.
     *
     * @param  Product         $product
     * @param  ProductVariant  $variant
     * @return ProductVariantResource|JsonResponse
     */
    public function show(Product $product, ProductVariant $variant): ProductVariantResource|JsonResponse
    {
        try {
            $variant->load('inventory');

            return new ProductVariantResource($variant);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_VARIANT');
        }
    }

    /**
     * Update a variant.
     *
     * If price fields are included in the payload, the UpdateVariantPriceAction
     * is used to atomically update prices and append a price history record.
     * Non-price fields are applied via a direct model update.
     *
     * @param  Request               $request
     * @param  Product               $product
     * @param  ProductVariant        $variant
     * @param  UpdateVariantPriceAction  $priceAction
     * @return ProductVariantResource|JsonResponse
     */
    public function update(
        Request                $request,
        Product                $product,
        ProductVariant         $variant,
        UpdateVariantPriceAction $priceAction,
    ): ProductVariantResource|JsonResponse {
        try {
            $validated = $request->validate([
                'sku'              => ['sometimes', 'string', 'max:100', 'unique:product_variants,sku,' . $variant->id],
                'barcode'          => ['nullable', 'string', 'max:100'],
                'attribute_values' => ['sometimes', 'array'],
                'compare_price'    => ['sometimes', 'integer', 'min:0'],
                'cost_price'       => ['sometimes', 'integer', 'min:0'],
                'selling_price'    => ['sometimes', 'integer', 'min:0'],
                'weight_grams'     => ['nullable', 'integer', 'min:1'],
                'status'           => ['nullable', 'in:active,inactive,discontinued'],
                'sort_order'       => ['nullable', 'integer'],
                'reason'           => ['nullable', 'string'],
            ]);

            $priceFields = ['compare_price', 'cost_price', 'selling_price'];
            $hasPriceChange = collect($priceFields)->some(
                fn (string $f) => array_key_exists($f, $validated)
            );

            if ($hasPriceChange) {
                $variant = $priceAction->execute(
                    variant:       $variant,
                    comparePrice:  (int) ($validated['compare_price'] ?? $variant->compare_price),
                    costPrice:     (int) ($validated['cost_price'] ?? $variant->cost_price),
                    sellingPrice:  (int) ($validated['selling_price'] ?? $variant->selling_price),
                    changedBy:     $request->user()?->id,
                    reason:        $validated['reason'] ?? null,
                );
            }

            $nonPriceData = array_diff_key($validated, array_flip([...$priceFields, 'reason']));

            if (!empty($nonPriceData)) {
                $variant->update($nonPriceData);
                $variant->refresh();
            }

            return new ProductVariantResource($variant);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_VARIANT');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_VARIANT');
        }
    }

    /**
     * Soft-delete a variant.
     *
     * @param  Product         $product
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        try {
            $variant->delete();

            return response()->json(['success' => true, 'message' => 'Variant deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_VARIANT');
        }
    }

    /**
     * Aggregate inventory stock for a variant across all warehouses.
     *
     * Returns total on-hand, total reserved, and available quantity,
     * plus a per-warehouse breakdown.
     *
     * @param  Product         $product
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function inventory(Product $product, ProductVariant $variant): JsonResponse
    {
        try {
            $variant->load('inventory');

            $inventoryRows = $variant->inventory;

            $totalOnHand  = $inventoryRows->sum('quantity_on_hand');
            $totalReserved = $inventoryRows->sum('quantity_reserved');

            $warehouses = $inventoryRows->map(fn ($inv) => [
                'warehouse_id'       => $inv->warehouse_id,
                'quantity_on_hand'   => $inv->quantity_on_hand,
                'quantity_reserved'  => $inv->quantity_reserved,
                'quantity_available' => max(0, $inv->quantity_on_hand - $inv->quantity_reserved),
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'variant_id'          => $variant->id,
                    'sku'                 => $variant->sku,
                    'total_on_hand'       => $totalOnHand,
                    'total_reserved'      => $totalReserved,
                    'total_available'     => max(0, $totalOnHand - $totalReserved),
                    'warehouses'          => $warehouses,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_VARIANT_INVENTORY');
        }
    }
}

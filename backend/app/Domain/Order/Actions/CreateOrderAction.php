<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Customer\Actions\CreateCustomerAction;
use App\Domain\Customer\Data\CreateCustomerDto;
use App\Domain\Commission\Services\CommissionCalculator;
use App\Domain\Customer\Models\Customer;
use App\Domain\Inventory\Models\Inventory;
use App\Domain\Order\Data\CreateOrderDto;
use App\Domain\Order\Data\CreateOrderItemDto;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Order\Services\OrderActivityLogger;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Shared\Traits\GeneratesCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Action to create a new customer order.
 *
 * Runs inside a database transaction to ensure the order, its items,
 * and the initial status history entry are created atomically.
 * Inventory reservations are triggered after successful persistence.
 */
class CreateOrderAction
{
    use GeneratesCode;

    public function __construct(
        private readonly OrderActivityLogger $activityLogger,
        private readonly CommissionCalculator $commissionCalculator,
        private readonly CreateCustomerAction $createCustomer
    ) {
    }

    /**
     * Execute the order creation.
     *
     * Processes each item, applies coupon and loyalty discounts, computes
     * VAT, generates an order number, persists all records, and reserves
     * inventory for each variant.
     *
     * @param  CreateOrderDto  $dto  Validated order creation payload.
     * @return Order                 The newly created order with items loaded.
     *
     * @throws \Throwable  On any failure; the transaction will roll back.
     */
    public function execute(CreateOrderDto $dto): Order
    {
        return DB::transaction(function () use ($dto): Order {
            // Resolve customer data if customer_id provided
            ['customer' => $customer, 'shipping' => $resolvedShipping] = $this->resolveCustomer($dto);

            $itemSnapshots = $this->buildItemSnapshots($dto->items);

            $subtotal = array_sum(array_map(
                fn(array $s) => $s['unit_price'] * $s['quantity'],
                $itemSnapshots
            ));

            [$couponDiscount, $couponId] = $this->resolveCouponDiscount($dto->couponCode, $subtotal);

            $loyaltyDiscount = $dto->loyaltyPointsToUse;

            $vatRate = $this->activeVatRate();
            $taxable = $subtotal - $couponDiscount - $loyaltyDiscount;
            $vatAmount = (int) round($taxable * $vatRate / 100);

            $totalBeforeTax = $taxable;
            $totalAfterTax = $taxable + $vatAmount;

            $sequence = Order::withTrashed()->count() + 1;
            $orderNumber = $this->buildOrderNumber($sequence);

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer?->id ?? $dto->customerId,
                'guest_name' => $dto->guestName ?? $customer?->fullName(),
                'guest_email' => $dto->guestEmail ?? $customer?->email,
                'guest_phone' => $dto->guestPhone ?? $customer?->phone,
                'payment_method' => $dto->paymentMethod,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'coupon_id' => $couponId,
                'coupon_code' => $dto->couponCode,
                'coupon_discount' => $couponDiscount,
                'loyalty_points_used' => $dto->loyaltyPointsToUse,
                'loyalty_discount' => $loyaltyDiscount,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount,
                'total_before_tax' => $totalBeforeTax,
                'total_after_tax' => $totalAfterTax,
                'shipping_name' => $resolvedShipping['name'] ?? $dto->shippingName,
                'shipping_phone' => $resolvedShipping['phone'] ?? $dto->shippingPhone,
                'shipping_province' => $resolvedShipping['province'] ?? $dto->shippingProvince,
                'shipping_district' => $resolvedShipping['district'] ?? $dto->shippingDistrict,
                'shipping_ward' => $resolvedShipping['ward'] ?? $dto->shippingWard,
                'shipping_address' => $resolvedShipping['address'] ?? $dto->shippingAddress,
                'shipping_zip' => $resolvedShipping['zip'] ?? $dto->shippingZip,
                'notes' => $dto->notes,
                'source' => $dto->source,
                'utm_source' => $dto->utmSource,
                'utm_medium' => $dto->utmMedium,
                'utm_campaign' => $dto->utmCampaign,
                'warehouse_id' => $dto->warehouseId,
                'referral_code' => $dto->referralCode,
                'tags' => [],
                'public_token' => $this->generatePublicToken(),
                'token_expires_at' => now()->addDays(30),
            ]);

            foreach ($itemSnapshots as $snapshot) {
                OrderItem::create(array_merge($snapshot, ['order_id' => $order->id]));
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'pending',
                'note' => 'Order created.',
                'is_customer_visible' => true,
                'created_at' => now(),
            ]);

            // Log order creation activity
            $this->activityLogger->logOrderCreated($order);

            $this->reserveInventory($order, $itemSnapshots, $dto->warehouseId);

            return $order->load('items');
        });
    }

    /**
     * Load product variants and snapshot their attributes for order persistence.
     *
     * @param  list<CreateOrderItemDto>  $items
     * @return list<array<string,mixed>>
     */
    private function buildItemSnapshots(array $items): array
    {
        $snapshots = [];

        foreach ($items as $item) {
            $variant = ProductVariant::with('product')->find($item->productVariantId);
            $product = null;
            $attributeValues = [];

            if ($variant !== null) {
                $product = $variant->product ?? Product::find($variant->product_id);
                $attributeValues = is_array($variant->attribute_values) ? $variant->attribute_values : [];

                $snapshots[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $product?->name ?? $variant->sku,
                    'size' => $this->resolveSnapshotSize($attributeValues, $item->size),
                    'color' => $this->resolveSnapshotAttribute($attributeValues, ['color', 'colour']),
                    'unit_price' => $item->unitPrice,
                    'cost_price' => $variant->cost_price ?? 0,
                    'quantity' => $item->quantity,
                    'quantity_returned' => 0,
                    'discount_amount' => 0,
                    'total_price' => $item->unitPrice * $item->quantity,
                ];

                continue;
            }

            $product = Product::findOrFail($item->productVariantId);

            $resolvedVariant = $this->resolveProductVariantFromProduct($product, $item->size);

            if ($resolvedVariant !== null) {
                $attributeValues = is_array($resolvedVariant->attribute_values)
                    ? $resolvedVariant->attribute_values
                    : [];

                $snapshots[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $resolvedVariant->id,
                    'sku' => $resolvedVariant->sku,
                    'name' => $product->name,
                    'size' => $this->resolveSnapshotSize($attributeValues, $item->size),
                    'color' => $this->resolveSnapshotAttribute($attributeValues, ['color', 'colour']),
                    'unit_price' => $item->unitPrice,
                    'cost_price' => $resolvedVariant->cost_price ?? 0,
                    'quantity' => $item->quantity,
                    'quantity_returned' => 0,
                    'discount_amount' => 0,
                    'total_price' => $item->unitPrice * $item->quantity,
                ];

                continue;
            }

            $snapshots[] = [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'sku' => $product->sku ?? $product->id,
                'name' => $product->name,
                'size' => $this->resolveSnapshotSize($attributeValues, $item->size),
                'color' => $this->resolveSnapshotAttribute($attributeValues, ['color', 'colour']),
                'unit_price' => $item->unitPrice,
                'cost_price' => $product->cost_price ?? 0,
                'quantity' => $item->quantity,
                'quantity_returned' => 0,
                'discount_amount' => 0,
                'total_price' => $item->unitPrice * $item->quantity,
            ];
        }

        return $snapshots;
    }

    /**
     * Resolve the displayed size snapshot from variant attributes and user input.
     *
     * @param  array<string,mixed>  $attributeValues
     * @param  string|null          $fallbackSize
     * @return string|null
     */
    private function resolveSnapshotSize(array $attributeValues, ?string $fallbackSize): ?string
    {
        $size = $this->resolveSnapshotAttribute($attributeValues, ['size', 'size_code', 'size_name', 'size_label']);

        return $size ?? $fallbackSize;
    }

    /**
     * Resolve a scalar snapshot attribute from a variant attribute array.
     *
     * @param  array<string,mixed>  $attributeValues
     * @param  array<int,string>    $keys
     * @return string|null
     */
    private function resolveSnapshotAttribute(array $attributeValues, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $attributeValues)) {
                continue;
            }

            $value = $attributeValues[$key];

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Resolve the concrete product variant for a product/size selection.
     *
     * @param  Product       $product
     * @param  string|null   $size
     * @return \App\Domain\Product\Models\ProductVariant|null
     */
    private function resolveProductVariantFromProduct(Product $product, ?string $size): ?\App\Domain\Product\Models\ProductVariant
    {
        $variants = ProductVariant::where('product_id', $product->id)
            ->where('status', 'active')
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        if ($size === null) {
            return $variants->first();
        }

        $normalizedSize = strtolower(trim($size));

        $matchedVariant = $variants->first(function (ProductVariant $variant) use ($normalizedSize): bool {
            $attributeValues = is_array($variant->attribute_values) ? $variant->attribute_values : [];

            $candidate = $this->resolveSnapshotAttribute($attributeValues, ['size', 'size_code', 'size_name', 'size_label']);

            if ($candidate === null) {
                return false;
            }

            return strtolower(trim($candidate)) === $normalizedSize;
        });

        return $matchedVariant instanceof ProductVariant ? $matchedVariant : $variants->first();
    }

    /**
     * Resolve coupon discount amount and coupon ID from a coupon code.
     *
     * @param  string|null  $couponCode
     * @param  int          $subtotal
     * @return array{0: int, 1: string|null}  [discountAmount, couponId]
     */
    private function resolveCouponDiscount(?string $couponCode, int $subtotal): array
    {
        if ($couponCode === null) {
            return [0, null];
        }

        $coupon = \App\Domain\Coupon\Models\Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return [0, null];
        }

        $discount = match ($coupon->type) {
            'percent' => (int) round($subtotal * $coupon->value / 100),
            'fixed_amount' => (int) $coupon->value,
            default => 0,
        };

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (int) $coupon->max_discount_amount);
        }

        return [$discount, $coupon->id];
    }

    /**
     * Retrieve the active VAT rate from the tax configuration.
     *
     * Defaults to 0 if no active tax config is found.
     *
     * @return float
     */
    private function activeVatRate(): float
    {
        $config = \App\Domain\Tax\Models\TaxConfig::where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->latest('effective_from')
            ->first();

        return $config ? (float) $config->rate : 0.0;
    }

    /**
     * Resolve customer from customer_id and return auto-filled shipping data.
     *
     * @param  CreateOrderDto  $dto
     * @return array{customer: Customer|null, shipping: array<string, string|null>}
     */
    private function resolveCustomer(CreateOrderDto $dto): array
    {
        if (!$dto->customerId) {
            return ['customer' => $this->createGuestCustomer($dto), 'shipping' => []];
        }

        $customer = Customer::with('addresses')->find($dto->customerId);

        if (!$customer) {
            return ['customer' => null, 'shipping' => []];
        }

        $shipping = [
            'name' => $dto->shippingName ?: $customer->fullName(),
            'phone' => $dto->shippingPhone ?: $customer->phone,
            'province' => $dto->shippingProvince,
            'district' => $dto->shippingDistrict,
            'ward' => $dto->shippingWard,
            'address' => $dto->shippingAddress,
            'zip' => $dto->shippingZip,
        ];

        // Auto-fill from default address if no shipping info provided
        if (!$dto->shippingAddress && !$dto->shippingProvince) {
            $defaultAddress = $customer->addresses->firstWhere('is_default', true)
                ?? $customer->addresses->first();

            if ($defaultAddress) {
                $shipping['name'] = $dto->shippingName ?: $defaultAddress->recipient_name;
                $shipping['phone'] = $dto->shippingPhone ?: $defaultAddress->phone;
                $shipping['province'] = $defaultAddress->province;
                $shipping['district'] = $defaultAddress->district;
                $shipping['ward'] = $defaultAddress->ward;
                $shipping['address'] = $defaultAddress->address_line;
                $shipping['zip'] = $defaultAddress->postal_code;
            }
        }

        return ['customer' => $customer, 'shipping' => $shipping];
    }

    /**
     * Create a guest customer record from the order payload when no customer
     * has been selected.
     *
     * @param  CreateOrderDto  $dto
     * @return Customer|null
     */
    private function createGuestCustomer(CreateOrderDto $dto): ?Customer
    {
        $guestName = trim((string) ($dto->guestName ?: $dto->shippingName));

        if ($guestName === '') {
            return null;
        }

        $nameParts = preg_split('/\s+/', $guestName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = array_shift($nameParts) ?: $guestName;
        $lastName = $nameParts ? implode(' ', $nameParts) : $firstName;

        return $this->createCustomer->execute(new CreateCustomerDto(
            firstName: $firstName,
            lastName: $lastName,
            email: $dto->guestEmail,
            phone: $dto->guestPhone,
            source: $dto->source,
            notes: $dto->notes,
        ));
    }

    /**
     * Reserve inventory for each line item in the given warehouse.
     *
     * Failures are logged but do not abort the order creation transaction,
     * as the actual inventory domain will enforce consistency separately.
     *
     * @param  Order                     $order
     * @param  list<array<string,mixed>> $snapshots
     * @param  string|null               $warehouseId
     */
    private function reserveInventory(Order $order, array $snapshots, ?string $warehouseId): void
    {
        if ($warehouseId === null) {
            return;
        }

        foreach ($snapshots as $snapshot) {
            try {
                /** @var Inventory|null $inventory */
                $inventory = Inventory::where('product_variant_id', $snapshot['product_variant_id'])
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                if ($inventory) {
                    $inventory->increment('quantity_reserved', $snapshot['quantity']);
                    $inventory->decrement('quantity_available', $snapshot['quantity']);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to reserve inventory for order item.', [
                    'order_id' => $order->id,
                    'product_variant_id' => $snapshot['product_variant_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Order\Actions;

use App\Domain\Commission\Services\CommissionCalculator;
use App\Domain\Order\Data\CreateOrderDto;
use App\Domain\Order\Data\CreateOrderItemDto;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Order\Models\OrderStatusHistory;
use App\Domain\Order\Services\OrderActivityLogger;
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
        private readonly CommissionCalculator $commissionCalculator
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
                'customer_id' => $dto->customerId,
                'guest_name' => $dto->guestName,
                'guest_email' => $dto->guestEmail,
                'guest_phone' => $dto->guestPhone,
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
                'shipping_name' => $dto->shippingName,
                'shipping_phone' => $dto->shippingPhone,
                'shipping_province' => $dto->shippingProvince,
                'shipping_district' => $dto->shippingDistrict,
                'shipping_ward' => $dto->shippingWard,
                'shipping_address' => $dto->shippingAddress,
                'shipping_zip' => $dto->shippingZip,
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
            $variant = \App\Domain\Product\Models\ProductVariant::findOrFail($item->productVariantId);

            $snapshots[] = [
                'product_variant_id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->product?->name ?? $variant->sku,
                'size' => $variant->attribute_values['size'] ?? null,
                'color' => $variant->attribute_values['color'] ?? null,
                'unit_price' => $item->unitPrice,
                'cost_price' => $variant->cost_price ?? 0,
                'quantity' => $item->quantity,
                'quantity_returned' => 0,
                'discount_amount' => 0,
                'total_price' => $item->unitPrice * $item->quantity,
            ];
        }

        return $snapshots;
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
                $inventory = \App\Domain\Inventory\Models\Inventory::where('product_variant_id', $snapshot['product_variant_id'])
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

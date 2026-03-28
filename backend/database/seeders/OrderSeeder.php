<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customer\Models\Customer;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\SizeOption;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Seed sample orders with individual line items for each physical product.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::limit(10)->get();
        $products = Product::with('category.sizeOptions')->limit(10)->get();
        $warehouses = Warehouse::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'confirmed', 'processing', 'packed', 'delivered', 'cancelled'];
        $paymentMethods = ['cod', 'bank_transfer', 'momo', 'vnpay'];

        $shippingData = [
            ['province' => 'TP. Hồ Chí Minh', 'district' => 'Quận 1', 'ward' => 'Phường Bến Nghé', 'address' => '123 Nguyễn Huệ'],
            ['province' => 'Hà Nội', 'district' => 'Quận Hoàn Kiếm', 'ward' => 'Phường Hàng Bài', 'address' => '456 Đinh Tiên Hoàng'],
            ['province' => 'Đà Nẵng', 'district' => 'Quận Hải Châu', 'ward' => 'Phường Thạch Thang', 'address' => '789 Bạch Đằng'],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $customer = $customers->get(($i - 1) % $customers->count());
            $product = $products->get(($i - 1) % $products->count());
            $warehouse = $warehouses->first();
            $shipping = $shippingData[$i % count($shippingData)];
            $status = $statuses[($i - 1) % count($statuses)];
            $payment = $paymentMethods[($i - 1) % count($paymentMethods)];

            $quantity = rand(1, 3);
            $unitPrice = $product?->selling_price ?? 300000;
            $subtotal = $unitPrice * $quantity;
            $shippingFee = $subtotal >= 500000 ? 0 : 30000;
            $totalBefore = $subtotal + $shippingFee;

            $date = now()->subDays(rand(1, 60));
            $orderNumber = 'DRP-' . $date->format('ymd') . '-' . sprintf('%04d', $i);

            $isPaid = in_array($status, ['delivered', 'packed'], true);

            $order = Order::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'order_number' => $orderNumber,
                    'customer_id' => $customer?->id,
                    'status' => $status,
                    'payment_status' => $isPaid ? 'paid' : 'unpaid',
                    'payment_method' => $payment,
                    'paid_at' => $isPaid ? $date->copy()->addHours(2) : null,
                    'subtotal' => $subtotal,
                    'coupon_discount' => 0,
                    'loyalty_points_used' => 0,
                    'loyalty_discount' => 0,
                    'shipping_fee' => $shippingFee,
                    'vat_rate' => 0,
                    'vat_amount' => 0,
                    'total_before_tax' => $totalBefore,
                    'total_after_tax' => $totalBefore,
                    'cost_total' => (int) round($subtotal * 0.6),
                    'shipping_name' => $customer ? ($customer->first_name . ' ' . $customer->last_name) : 'Khách lẻ',
                    'shipping_phone' => $customer?->phone ?? '0901234567',
                    'shipping_province' => $shipping['province'],
                    'shipping_district' => $shipping['district'],
                    'shipping_ward' => $shipping['ward'],
                    'shipping_address' => $shipping['address'],
                    'warehouse_id' => $warehouse?->id,
                    'source' => 'website',
                    'tags' => [],
                    'confirmed_at' => in_array($status, ['confirmed', 'processing', 'packed', 'delivered'], true) ? $date->copy()->addHour() : null,
                    'delivered_at' => $status === 'delivered' ? $date->copy()->addDays(3) : null,
                    'cancelled_at' => $status === 'cancelled' ? $date->copy()->addHours(2) : null,
                    'cancellation_reason' => $status === 'cancelled' ? 'Khách hàng yêu cầu hủy đơn' : null,
                ],
            );

            // Get a random size option for this product's category
            $sizeOption = null;
            if ($product?->category?->sizeOptions?->isNotEmpty()) {
                $sizeOption = $product->category->sizeOptions->random();
            }

            // Create individual order item rows (one per quantity)
            for ($q = 0; $q < $quantity; $q++) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'sku' => $product?->sku ?? 'DRP-SKU-DEFAULT',
                    'name' => $product?->name ?? 'Sản phẩm Driip',
                    'size_option_id' => $sizeOption?->id,
                    'color' => null,
                    'unit_price' => $unitPrice,
                    'cost_price' => $product?->cost_price ?? (int) round($unitPrice * 0.6),
                    'discount_amount' => 0,
                    'status' => $this->resolveItemStatus($order->status),
                ]);
            }
        }
    }

    /**
     * Resolve order item status from parent order status.
     */
    private function resolveItemStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'pending', 'confirmed', 'processing' => 'pending',
            'packed', 'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}

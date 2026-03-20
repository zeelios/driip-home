<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\PurchaseOrder;
use App\Domain\Inventory\Models\Supplier;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating PurchaseOrder model instances.
 *
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $shippingCost = $this->faker->randomElement([0, 50000, 100000, 200000]);
        $otherCosts   = $this->faker->randomElement([0, 50000, 100000]);
        $totalCost    = $this->faker->numberBetween(1000000, 50000000) + $shippingCost + $otherCosts;

        return [
            'po_number'           => sprintf('DRP-PO-%04d', $counter),
            'supplier_id'         => Supplier::factory(),
            'warehouse_id'        => Warehouse::factory(),
            'status'              => 'draft',
            'expected_arrival_at' => $this->faker->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            'received_at'         => null,
            'shipping_cost'       => $shippingCost,
            'other_costs'         => $otherCosts,
            'total_cost'          => $totalCost,
            'notes'               => $this->faker->optional(0.3)->sentence(),
            'created_by'          => User::factory(),
            'approved_by'         => null,
            'approved_at'         => null,
        ];
    }

    /**
     * State for an approved purchase order.
     *
     * @return static
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * State for a received (completed) purchase order.
     *
     * @return static
     */
    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'received',
            'approved_by' => User::factory(),
            'approved_at' => now()->subDays(5),
            'received_at' => now(),
        ]);
    }
}

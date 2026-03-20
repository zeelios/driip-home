<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Staff\Models\User;
use App\Domain\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for generating StockTransfer model instances.
 *
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    /** @var string The model this factory is for. */
    protected $model = StockTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $counter = 0;
        $counter++;

        $reasons = [
            'Điều phối hàng tồn kho giữa các kho',
            'Bổ sung hàng cho kho thiếu hụt',
            'Chuyển hàng về kho trung tâm',
            'Hỗ trợ đơn hàng khu vực',
        ];

        return [
            'transfer_number'   => sprintf('DRP-ST-%04d', $counter),
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id'   => Warehouse::factory(),
            'status'            => 'draft',
            'reason'            => $this->faker->randomElement($reasons),
            'requested_by'      => User::factory(),
            'approved_by'       => null,
            'dispatched_at'     => null,
            'received_at'       => null,
            'notes'             => null,
        ];
    }

    /**
     * State for an approved stock transfer.
     *
     * @return static
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'approved',
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * State for a dispatched stock transfer.
     *
     * @return static
     */
    public function dispatched(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'        => 'dispatched',
            'approved_by'   => User::factory(),
            'dispatched_at' => now()->subDay(),
        ]);
    }
}

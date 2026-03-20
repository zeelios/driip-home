<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * Data transfer object carrying the input required to create a new shipment.
 */
class CreateShipmentDto
{
    /**
     * @param  string      $orderId      UUID of the order being shipped.
     * @param  string      $courierCode  Courier identifier (e.g. ghn, ghtk).
     * @param  int         $codAmount    Cash-on-delivery amount in VND (0 if prepaid).
     * @param  float|null  $weightKg     Package weight in kilograms.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $courierCode,
        public readonly int    $codAmount,
        public readonly ?float $weightKg,
    ) {}

    /**
     * Instantiate the DTO from a validated request array.
     *
     * @param  array<string,mixed>  $data  Validated request data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderId:     $data['order_id'],
            courierCode: $data['courier_code'],
            codAmount:   (int) $data['cod_amount'],
            weightKg:    isset($data['weight_kg']) ? (float) $data['weight_kg'] : null,
        );
    }
}

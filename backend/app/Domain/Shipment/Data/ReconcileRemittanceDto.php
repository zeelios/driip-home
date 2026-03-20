<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * Data transfer object carrying the input required to reconcile a COD remittance.
 */
class ReconcileRemittanceDto
{
    /**
     * @param  string                                                         $remittanceId  UUID of the remittance batch to reconcile.
     * @param  array<int,array{tracking_number:string,cod_amount:int,shipping_fee:int}>  $items  Line items from the courier statement.
     */
    public function __construct(
        public readonly string $remittanceId,
        public readonly array  $items,
    ) {}

    /**
     * Instantiate the DTO from a validated request array.
     *
     * @param  string               $remittanceId  UUID of the remittance batch.
     * @param  array<string,mixed>  $data          Validated request data.
     * @return self
     */
    public static function fromArray(string $remittanceId, array $data): self
    {
        $items = array_map(static function (array $item): array {
            return [
                'tracking_number' => $item['tracking_number'],
                'cod_amount'      => (int) $item['cod_amount'],
                'shipping_fee'    => (int) $item['shipping_fee'],
            ];
        }, $data['items'] ?? []);

        return new self(
            remittanceId: $remittanceId,
            items:        $items,
        );
    }
}

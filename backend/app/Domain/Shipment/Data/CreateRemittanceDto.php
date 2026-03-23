<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * Auto-generated DTO for CreateRemittanceRequest.
 * Source of truth: validation rules in CreateRemittanceRequest.
 */
readonly class CreateRemittanceDto
{
    public function __construct(
        public string $courierCode,
        public ?string $remittanceReference = null,
        public string $periodFrom,
        public string $periodTo,
        public int $totalCodCollected,
        public int $totalFeesDeducted,
        public int $netRemittance,
        public ?string $notes = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            courierCode: $data['courier_code'],
            remittanceReference: $data['remittance_reference'] ?? null,
            periodFrom: $data['period_from'],
            periodTo: $data['period_to'],
            totalCodCollected: $data['total_cod_collected'],
            totalFeesDeducted: $data['total_fees_deducted'],
            netRemittance: $data['net_remittance'],
            notes: $data['notes'] ?? null
        );
    }
}

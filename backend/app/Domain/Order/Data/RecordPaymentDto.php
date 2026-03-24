<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Data transfer object for recording a payment.
 *
 * @property int               $amount
 * @property string            $paymentMethod
 * @property string            $paymentType
 * @property string|null       $reference
 * @property list<string>      $proofUrls
 * @property string|null       $notes
 * @property string|null       $recordedBy
 */
class RecordPaymentDto
{
    public function __construct(
        public readonly int $amount,
        public readonly string $paymentMethod,
        public readonly string $paymentType,
        public readonly ?string $reference = null,
        public readonly array $proofUrls = [],
        public readonly ?string $notes = null,
        public readonly ?string $recordedBy = null,
    ) {
    }

    /**
     * Create a DTO from an array of validated data.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: (int) $data['amount'],
            paymentMethod: $data['payment_method'],
            paymentType: $data['payment_type'],
            reference: $data['reference'] ?? null,
            proofUrls: $data['proof_urls'] ?? [],
            notes: $data['notes'] ?? null,
            recordedBy: $data['recorded_by'] ?? null,
        );
    }
}

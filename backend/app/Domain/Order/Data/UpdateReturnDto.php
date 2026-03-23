<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Auto-generated DTO for UpdateReturnRequest.
 * Source of truth: validation rules in UpdateReturnRequest.
 */
readonly class UpdateReturnDto
{
    public function __construct(
        public ?string $status = null,
        public ?string $returnCourier = null,
        public ?string $returnTracking = null,
        public ?int $totalRefund = null,
        public ?string $refundMethod = null,
        public ?string $refundReference = null,
        public ?string $refundedAt = null,
        public ?string $receivedAt = null,
        public ?string $processedBy = null,
        public ?string $notes = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? null,
            returnCourier: $data['return_courier'] ?? null,
            returnTracking: $data['return_tracking'] ?? null,
            totalRefund: $data['total_refund'] ?? null,
            refundMethod: $data['refund_method'] ?? null,
            refundReference: $data['refund_reference'] ?? null,
            refundedAt: $data['refunded_at'] ?? null,
            receivedAt: $data['received_at'] ?? null,
            processedBy: $data['processed_by'] ?? null,
            notes: $data['notes'] ?? null
        );
    }
}

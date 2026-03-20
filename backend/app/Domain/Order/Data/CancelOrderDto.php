<?php

declare(strict_types=1);

namespace App\Domain\Order\Data;

/**
 * Data Transfer Object for cancelling an order.
 *
 * Carries the mandatory human-readable cancellation reason that
 * will be stored on the order and surfaced to the customer.
 */
readonly class CancelOrderDto
{
    /**
     * Create a new CancelOrderDto.
     *
     * @param  string  $reason  Human-readable reason for the cancellation.
     */
    public function __construct(
        public string $reason,
    ) {}

    /**
     * Build from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reason: (string) ($data['reason'] ?? ''),
        );
    }
}

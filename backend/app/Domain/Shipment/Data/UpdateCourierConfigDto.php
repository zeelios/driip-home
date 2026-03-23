<?php

declare(strict_types=1);

namespace App\Domain\Shipment\Data;

/**
 * Auto-generated DTO for UpdateCourierConfigRequest.
 * Source of truth: validation rules in UpdateCourierConfigRequest.
 */
readonly class UpdateCourierConfigDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $apiEndpoint = null,
        public ?string $apiKey = null,
        public ?string $apiSecret = null,
        public ?string $accountId = null,
        public ?string $pickupHubCode = null,
        public array $pickupAddress = [],
        public ?string $webhookSecret = null,
        public ?bool $isActive = null,
        public array $settings = []
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            apiEndpoint: $data['api_endpoint'] ?? null,
            apiKey: $data['api_key'] ?? null,
            apiSecret: $data['api_secret'] ?? null,
            accountId: $data['account_id'] ?? null,
            pickupHubCode: $data['pickup_hub_code'] ?? null,
            pickupAddress: $data['pickup_address'] ?? [],
            webhookSecret: $data['webhook_secret'] ?? null,
            isActive: $data['is_active'] ?? null,
            settings: $data['settings'] ?? []
        );
    }
}

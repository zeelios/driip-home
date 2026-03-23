<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

/**
 * Auto-generated DTO for UpdateBrandRequest.
 * Source of truth: validation rules in UpdateBrandRequest.
 */
readonly class UpdateBrandDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $logoUrl = null,
        public ?string $website = null,
        public ?string $country = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            logoUrl: $data['logo_url'] ?? null,
            website: $data['website'] ?? null,
            country: $data['country'] ?? null,
            isActive: $data['is_active'] ?? null,
            sortOrder: $data['sort_order'] ?? null
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

/**
 * Auto-generated DTO for UpdateVariantRequest.
 * Source of truth: validation rules in UpdateVariantRequest.
 */
readonly class UpdateVariantDto
{
    public function __construct(
        public ?string $sku = null,
        public ?string $barcode = null,
        public array $attributeValues = [],
        public ?int $comparePrice = null,
        public ?int $costPrice = null,
        public ?int $sellingPrice = null,
        public ?int $weightGrams = null,
        public ?string $status = null,
        public ?int $sortOrder = null,
        public ?string $reason = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sku: $data['sku'] ?? null,
            barcode: $data['barcode'] ?? null,
            attributeValues: $data['attribute_values'] ?? [],
            comparePrice: $data['compare_price'] ?? null,
            costPrice: $data['cost_price'] ?? null,
            sellingPrice: $data['selling_price'] ?? null,
            weightGrams: $data['weight_grams'] ?? null,
            status: $data['status'] ?? null,
            sortOrder: $data['sort_order'] ?? null,
            reason: $data['reason'] ?? null
        );
    }
}

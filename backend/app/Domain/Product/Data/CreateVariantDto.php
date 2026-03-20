<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

/**
 * Data Transfer Object for creating a new product variant.
 *
 * Carries all fields required to persist a variant and its initial price
 * history entry in a single atomic operation.
 */
readonly class CreateVariantDto
{
    /**
     * @param  string              $productId        UUID of the parent product.
     * @param  string              $sku              Unique stock-keeping unit code.
     * @param  string|null         $barcode          Optional EAN/barcode for the variant.
     * @param  array<string,mixed> $attributeValues  Map of attribute_id → value_id pairs.
     * @param  int                 $comparePrice     MSRP / strike-through price in VND.
     * @param  int                 $costPrice        Landed cost price in VND.
     * @param  int                 $sellingPrice     Standard retail price in VND.
     * @param  int                 $weightGrams      Shipping weight in grams.
     * @param  string              $status           Variant status: active, inactive, discontinued.
     */
    public function __construct(
        public string  $productId,
        public string  $sku,
        public ?string $barcode,
        public array   $attributeValues,
        public int     $comparePrice,
        public int     $costPrice,
        public int     $sellingPrice,
        public int     $weightGrams = 200,
        public string  $status      = 'active',
    ) {}

    /**
     * Build a CreateVariantDto from a validated request array plus the product UUID.
     *
     * @param  string              $productId  UUID of the parent product.
     * @param  array<string,mixed> $data       Validated input from CreateVariantRequest.
     * @return self
     */
    public static function fromArray(string $productId, array $data): self
    {
        return new self(
            productId:       $productId,
            sku:             $data['sku'],
            barcode:         $data['barcode'] ?? null,
            attributeValues: $data['attribute_values'] ?? [],
            comparePrice:    (int) $data['compare_price'],
            costPrice:       (int) $data['cost_price'],
            sellingPrice:    (int) $data['selling_price'],
            weightGrams:     isset($data['weight_grams']) ? (int) $data['weight_grams'] : 200,
            status:          $data['status'] ?? 'active',
        );
    }
}

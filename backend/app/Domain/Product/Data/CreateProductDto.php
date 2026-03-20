<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

use Illuminate\Support\Str;

/**
 * Data Transfer Object for creating a new product.
 *
 * If slug is not provided it will be auto-generated from the product name
 * using kebab-case slugification.
 */
readonly class CreateProductDto
{
    /**
     * @param  string              $name              Display name of the product.
     * @param  string              $slug              URL slug (auto-generated from name if not supplied).
     * @param  string|null         $brandId           UUID of the associated brand, if any.
     * @param  string|null         $categoryId        UUID of the associated category, if any.
     * @param  string|null         $description       Long-form HTML/text description.
     * @param  string|null         $shortDescription  Short marketing blurb (max 500 chars).
     * @param  string|null         $skuBase           Base SKU prefix shared across all variants.
     * @param  string|null         $gender            Target gender: men, women, unisex, kids.
     * @param  string|null         $season            Season code e.g. SS26, FW25, ALL.
     * @param  array<int,string>   $tags              Flat list of tag strings.
     * @param  string              $status            Lifecycle status: draft, active, archived.
     */
    public function __construct(
        public string  $name,
        public string  $slug,
        public ?string $brandId,
        public ?string $categoryId,
        public ?string $description,
        public ?string $shortDescription,
        public ?string $skuBase,
        public ?string $gender,
        public ?string $season,
        public array   $tags   = [],
        public string  $status = 'draft',
    ) {}

    /**
     * Build a CreateProductDto from a validated request array.
     *
     * The slug is auto-generated from the name when not explicitly provided.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $name = $data['name'];
        $slug = $data['slug'] ?? Str::slug($name);

        return new self(
            name:             $name,
            slug:             $slug,
            brandId:          $data['brand_id'] ?? null,
            categoryId:       $data['category_id'] ?? null,
            description:      $data['description'] ?? null,
            shortDescription: $data['short_description'] ?? null,
            skuBase:          $data['sku_base'] ?? null,
            gender:           $data['gender'] ?? null,
            season:           $data['season'] ?? null,
            tags:             $data['tags'] ?? [],
            status:           $data['status'] ?? 'draft',
        );
    }
}

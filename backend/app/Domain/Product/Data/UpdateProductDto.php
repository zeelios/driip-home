<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

use Illuminate\Support\Str;

/**
 * Data Transfer Object for updating an existing product.
 *
 * All fields are nullable/optional. Only non-null values will be applied
 * during the update, allowing partial patch-style modifications.
 */
readonly class UpdateProductDto
{
    /**
     * @param  string|null         $name              Display name of the product.
     * @param  string|null         $slug              URL slug (auto-generated from name if not supplied but name changed).
     * @param  string|null         $brandId           UUID of the associated brand.
     * @param  string|null         $categoryId        UUID of the associated category.
     * @param  string|null         $description       Long-form HTML/text description.
     * @param  string|null         $shortDescription  Short marketing blurb.
     * @param  string|null         $skuBase           Base SKU prefix shared across all variants.
     * @param  string|null         $gender            Target gender: men, women, unisex, kids.
     * @param  string|null         $season            Season code e.g. SS26, FW25, ALL.
     * @param  array<int,string>|null $tags           Flat list of tag strings.
     * @param  string|null         $status            Lifecycle status: draft, active, archived.
     * @param  bool|null           $isFeatured        Whether the product is featured.
     * @param  string|null         $publishedAt       ISO-8601 publish date.
     * @param  string|null         $metaTitle         SEO title override.
     * @param  string|null         $metaDescription   SEO description override.
     */
    public function __construct(
        public ?string $name             = null,
        public ?string $slug             = null,
        public ?string $brandId          = null,
        public ?string $categoryId       = null,
        public ?string $description      = null,
        public ?string $shortDescription = null,
        public ?string $skuBase          = null,
        public ?string $gender           = null,
        public ?string $season           = null,
        public ?array  $tags             = null,
        public ?string $status           = null,
        public ?bool   $isFeatured       = null,
        public ?string $publishedAt      = null,
        public ?string $metaTitle        = null,
        public ?string $metaDescription  = null,
    ) {}

    /**
     * Build an UpdateProductDto from a validated request array.
     *
     * @param  array<string,mixed>  $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:             $data['name'] ?? null,
            slug:             $data['slug'] ?? (isset($data['name']) ? Str::slug($data['name']) : null),
            brandId:          $data['brand_id'] ?? null,
            categoryId:       $data['category_id'] ?? null,
            description:      $data['description'] ?? null,
            shortDescription: $data['short_description'] ?? null,
            skuBase:          $data['sku_base'] ?? null,
            gender:           $data['gender'] ?? null,
            season:           $data['season'] ?? null,
            tags:             $data['tags'] ?? null,
            status:           $data['status'] ?? null,
            isFeatured:       isset($data['is_featured']) ? (bool) $data['is_featured'] : null,
            publishedAt:      $data['published_at'] ?? null,
            metaTitle:        $data['meta_title'] ?? null,
            metaDescription:  $data['meta_description'] ?? null,
        );
    }

    /**
     * Return only the non-null values as a key-value array suitable for Model::update().
     *
     * @return array<string,mixed>
     */
    public function toUpdateArray(): array
    {
        $map = [
            'name'              => $this->name,
            'slug'              => $this->slug,
            'brand_id'          => $this->brandId,
            'category_id'       => $this->categoryId,
            'description'       => $this->description,
            'short_description' => $this->shortDescription,
            'sku_base'          => $this->skuBase,
            'gender'            => $this->gender,
            'season'            => $this->season,
            'tags'              => $this->tags,
            'status'            => $this->status,
            'is_featured'       => $this->isFeatured,
            'published_at'      => $this->publishedAt,
            'meta_title'        => $this->metaTitle,
            'meta_description'  => $this->metaDescription,
        ];

        return array_filter($map, fn ($v) => $v !== null);
    }
}

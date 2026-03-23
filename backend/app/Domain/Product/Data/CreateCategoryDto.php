<?php

declare(strict_types=1);

namespace App\Domain\Product\Data;

/**
 * Auto-generated DTO for CreateCategoryRequest.
 * Source of truth: validation rules in CreateCategoryRequest.
 */
readonly class CreateCategoryDto
{
    public function __construct(
        public ?string $parentId = null,
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $imageUrl = null,
        public ?bool $isActive = null,
        public ?int $sortOrder = null
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            parentId: $data['parent_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            imageUrl: $data['image_url'] ?? null,
            isActive: $data['is_active'] ?? null,
            sortOrder: $data['sort_order'] ?? null
        );
    }
}

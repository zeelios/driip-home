<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for creating a new category.
 *
 * Auto-generates a URL slug from the category name when no slug is provided.
 * Validates slug uniqueness before persisting.
 */
class CreateCategoryAction
{
    /**
     * Execute the category creation.
     *
     * @param  array<string,mixed>  $data  Validated category data fields.
     * @return Category                     The newly created category instance with children loaded.
     *
     * @throws ValidationException  If the slug is already taken.
     */
    public function execute(array $data): Category
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        if (Category::withTrashed()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["The slug '{$slug}' is already in use."],
            ]);
        }

        $category = Category::create([
            'parent_id'   => $data['parent_id'] ?? null,
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'image_url'   => $data['image_url'] ?? $data['image'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);

        return $category->load('children');
    }
}

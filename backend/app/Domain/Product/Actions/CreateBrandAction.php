<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Brand;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for creating a new brand.
 *
 * Auto-generates a URL slug from the brand name when no slug is provided.
 * Validates slug uniqueness before persisting to prevent duplicate entries.
 */
class CreateBrandAction
{
    /**
     * Execute the brand creation.
     *
     * @param  array<string,mixed>  $data  Validated brand data fields.
     * @return Brand                        The newly created brand instance.
     *
     * @throws ValidationException  If the slug is already taken.
     */
    public function execute(array $data): Brand
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        if (Brand::withTrashed()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ["The slug '{$slug}' is already in use."],
            ]);
        }

        return Brand::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'logo_url'    => $data['logo_url'] ?? $data['logo'] ?? null,
            'description' => $data['description'] ?? null,
            'website'     => $data['website'] ?? null,
            'country'     => $data['country'] ?? 'VN',
            'is_active'   => $data['is_active'] ?? true,
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);
    }
}

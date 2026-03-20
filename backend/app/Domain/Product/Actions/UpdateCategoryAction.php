<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\Models\Category;
use Illuminate\Validation\ValidationException;

/**
 * Action responsible for updating an existing category's fields.
 *
 * If a slug is included in the payload, it is validated for uniqueness
 * against all other categories (excluding the current record).
 */
class UpdateCategoryAction
{
    /**
     * Execute the category update.
     *
     * @param  Category             $category  The category model to update.
     * @param  array<string,mixed>  $data      Validated partial category data.
     * @return Category                         The refreshed category instance with children loaded.
     *
     * @throws ValidationException  If the provided slug is already taken by another category.
     */
    public function execute(Category $category, array $data): Category
    {
        if (isset($data['slug'])) {
            $conflictExists = Category::withTrashed()
                ->where('slug', $data['slug'])
                ->where('id', '!=', $category->id)
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'slug' => ["The slug '{$data['slug']}' is already in use."],
                ]);
            }
        }

        $category->update($data);

        return $category->fresh()->load('children');
    }
}

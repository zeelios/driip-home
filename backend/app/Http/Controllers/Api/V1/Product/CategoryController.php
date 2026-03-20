<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Product;

use App\Domain\Product\Models\Category;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Product\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Controller for category CRUD operations.
 *
 * Supports hierarchical categories — child categories are included
 * via the 'children' eager-load relationship on show/list endpoints.
 */
class CategoryController extends BaseApiController
{
    /**
     * List all categories with optional filtering.
     *
     * Allowed filters: name, is_active, parent_id.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $categories = QueryBuilder::for(Category::class)
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('is_active'),
                    AllowedFilter::exact('parent_id'),
                )
                ->allowedSorts('name', 'sort_order', 'created_at')
                ->with('children')
                ->paginate(20);

            return CategoryResource::collection($categories);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'LIST_CATEGORIES');
        }
    }

    /**
     * Create a new category.
     *
     * @param  Request  $request
     * @return CategoryResource|JsonResponse
     */
    public function store(Request $request): CategoryResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
                'name'        => ['required', 'string', 'max:255'],
                'slug'        => ['required', 'string', 'max:255', 'unique:categories,slug'],
                'description' => ['nullable', 'string'],
                'image'       => ['nullable', 'string', 'max:500'],
                'sort_order'  => ['nullable', 'integer'],
                'is_active'   => ['nullable', 'boolean'],
            ]);

            $category = Category::create($validated);

            return new CategoryResource($category->load('children'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'CREATE_CATEGORY');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'CREATE_CATEGORY');
        }
    }

    /**
     * Retrieve a single category by its UUID, including its children.
     *
     * @param  Category  $category
     * @return CategoryResource|JsonResponse
     */
    public function show(Category $category): CategoryResource|JsonResponse
    {
        try {
            return new CategoryResource($category->load('children'));
        } catch (\Throwable $e) {
            return $this->serverError($e, 'SHOW_CATEGORY');
        }
    }

    /**
     * Update an existing category.
     *
     * @param  Request   $request
     * @param  Category  $category
     * @return CategoryResource|JsonResponse
     */
    public function update(Request $request, Category $category): CategoryResource|JsonResponse
    {
        try {
            $validated = $request->validate([
                'parent_id'   => ['nullable', 'uuid', 'exists:categories,id'],
                'name'        => ['sometimes', 'string', 'max:255'],
                'slug'        => ['sometimes', 'string', 'max:255', 'unique:categories,slug,' . $category->id],
                'description' => ['nullable', 'string'],
                'image'       => ['nullable', 'string', 'max:500'],
                'sort_order'  => ['nullable', 'integer'],
                'is_active'   => ['nullable', 'boolean'],
            ]);

            $category->update($validated);

            return new CategoryResource($category->fresh()->load('children'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e, 'UPDATE_CATEGORY');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'UPDATE_CATEGORY');
        }
    }

    /**
     * Soft-delete a category.
     *
     * @param  Category  $category
     * @return JsonResponse
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $category->delete();

            return response()->json(['success' => true, 'message' => 'Category deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError($e, 'DELETE_CATEGORY');
        }
    }
}

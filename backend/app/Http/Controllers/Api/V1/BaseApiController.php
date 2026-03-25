<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * Base controller for all v1 API endpoints.
 *
 * Provides shared error-handling helpers so every child controller
 * can produce uniform ErrorResource responses without boilerplate.
 */
abstract class BaseApiController extends Controller
{
    /**
     * Role hierarchy levels for permission checks.
     */
    protected const ROLE_LEVELS = [
        'warehouse-staff' => 1,
        'sales-staff' => 2,
        'manager' => 3,
        'admin' => 4,
        'super-admin' => 5,
    ];

    /**
     * Return a 422 validation error response.
     *
     * @param  ValidationException  $e
     * @param  string               $actionName  Uppercase action name, e.g. "CREATE_STAFF".
     */
    protected function validationError(ValidationException $e, string $actionName): JsonResponse
    {
        return ErrorResource::fromException($e, $actionName)
            ->response()
            ->setStatusCode(422);
    }

    /**
     * Return a 500 server error response.
     *
     * @param  \Throwable  $e
     * @param  string      $actionName
     */
    protected function serverError(\Throwable $e, string $actionName): JsonResponse
    {
        report($e);

        return ErrorResource::fromException($e, $actionName)
            ->response()
            ->setStatusCode(500);
    }

    /**
     * Return a 404 not-found response.
     *
     * @param  string  $actionName
     * @param  string  $message
     */
    protected function notFound(string $actionName, string $message = 'Resource not found.'): JsonResponse
    {
        return ErrorResource::fromException($message, $actionName)
            ->response()
            ->setStatusCode(404);
    }

    /**
     * Return a 403 forbidden response.
     *
     * @param  string  $actionName
     * @param  string  $message
     */
    protected function forbidden(string $actionName, string $message = 'Forbidden.'): JsonResponse
    {
        return ErrorResource::fromException($message, $actionName)
            ->response()
            ->setStatusCode(403);
    }

    /**
     * Check if the current user owns the given resource.
     *
     * @param  Model   $model
     * @param  string  $field  Field to compare against user ID (default: 'user_id')
     * @return bool
     */
    protected function checkOwnership(Model $model, string $field = 'user_id'): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->id === $model->{$field};
    }

    /**
     * Check if the current user owns the resource via any of the given fields.
     *
     * @param  Model          $model
     * @param  array<string>  $fields
     * @return bool
     */
    protected function checkOwnershipAny(Model $model, array $fields): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        foreach ($fields as $field) {
            if ($user->id === $model->{$field}) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the role level of the authenticated user.
     *
     * @return int
     */
    protected function getRoleLevel(): int
    {
        $user = auth()->user();

        if ($user === null) {
            return 0;
        }

        $roles = $user->roles->pluck('name')->toArray();
        $maxLevel = 0;

        foreach ($roles as $role) {
            $level = self::ROLE_LEVELS[$role] ?? 0;
            if ($level > $maxLevel) {
                $maxLevel = $level;
            }
        }

        return $maxLevel;
    }

    /**
     * Check if current user has higher role level than target user.
     *
     * @param  \App\Domain\Staff\Models\User  $targetUser
     * @return bool
     */
    protected function hasHigherRoleLevelThan(\App\Domain\Staff\Models\User $targetUser): bool
    {
        $currentLevel = $this->getRoleLevel();
        $targetRoles = $targetUser->roles->pluck('name')->toArray();

        $targetLevel = 0;
        foreach ($targetRoles as $role) {
            $level = self::ROLE_LEVELS[$role] ?? 0;
            if ($level > $targetLevel) {
                $targetLevel = $level;
            }
        }

        return $currentLevel > $targetLevel;
    }
}

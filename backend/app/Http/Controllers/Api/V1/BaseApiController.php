<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ErrorResource;
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
}

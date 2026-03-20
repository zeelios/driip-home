<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

/**
 * Uniform error response resource.
 *
 * Every failed request returns this shape:
 * {
 *   "success": false,
 *   "request_code": "CREATE_STAFF_A1B2C3D4",
 *   "message": "Human-readable message",
 *   "errors": { "field": ["detail"] }   // only for validation errors
 * }
 *
 * The request_code is a unique, traceable identifier composed of the action
 * name (e.g., CREATE_STAFF) and 8 random hex characters. Staff can log this
 * code and developers can look it up to trace the exact payload and context.
 */
class ErrorResource extends JsonResource
{
    /** @var string The unique request code for this failure. */
    protected string $requestCode;

    /** @var array<string,mixed> Validation error bag (if applicable). */
    protected array $validationErrors = [];

    /**
     * Build an ErrorResource from any throwable or plain message.
     *
     * @param  \Throwable|string  $error      The exception or message.
     * @param  string             $actionName Uppercase action identifier, e.g. "CREATE_STAFF".
     */
    public static function fromException(\Throwable|string $error, string $actionName): self
    {
        $code    = strtoupper($actionName) . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $message = $error instanceof \Throwable ? $error->getMessage() : $error;
        $errors  = [];

        if ($error instanceof ValidationException) {
            $errors = $error->errors();
        }

        $instance               = new static(['message' => $message]);
        $instance->requestCode  = $code;
        $instance->validationErrors = $errors;

        return $instance;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'success'      => false,
            'request_code' => $this->requestCode,
            'message'      => $this->resource['message'],
        ];

        if (!empty($this->validationErrors)) {
            $payload['errors'] = $this->validationErrors;
        }

        return $payload;
    }
}

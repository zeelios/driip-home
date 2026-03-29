<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as RequestFacade;
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

    /** @var string The action name for this failure. */
    protected string $actionName;

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
        $code = strtoupper($actionName) . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $message = $error instanceof \Throwable ? $error->getMessage() : $error;
        $errors = [];

        if ($error instanceof ValidationException) {
            $errors = $error->errors();
        }

        $instance = new static(['message' => $message]);
        $instance->requestCode = $code;
        $instance->actionName = strtoupper($actionName);
        $instance->validationErrors = $errors;

        $instance->logError($error);

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
            'success' => false,
            'request_code' => $this->requestCode,
            'message' => $this->resource['message'],
        ];

        if (!empty($this->validationErrors)) {
            $payload['errors'] = $this->validationErrors;
        }

        return $payload;
    }

    /**
     * Log the error with full context to laravel.log.
     *
     * @param \Throwable|string $error
     */
    protected function logError(\Throwable|string $error): void
    {
        $level = match (true) {
            $error instanceof ValidationException => 'warning',
            $error instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => 'info',
            $error instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException => 'warning',
            default => 'error',
        };

        $context = [
            'request_code' => $this->requestCode,
            'action' => $this->actionName,
            'user' => $this->getUserContext(),
            'request' => $this->getRequestContext(),
        ];

        if ($error instanceof \Throwable) {
            $context['exception_class'] = get_class($error);
            $context['file'] = $error->getFile();
            $context['line'] = $error->getLine();
        }

        Log::{$level}("[{$this->requestCode}] {$this->actionName} failed", $context);

        if ($error instanceof \Throwable) {
            Log::{$level}($error->getMessage(), ['request_code' => $this->requestCode]);
        }
    }

    /**
     * Get the authenticated user context.
     *
     * @return array<string,mixed>|null
     */
    protected function getUserContext(): ?array
    {
        $user = Auth::user();

        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id ?? null,
            'email' => $user->email ?? null,
            'name' => $user->name ?? null,
        ];
    }

    /**
     * Get the current request context with sanitized payload.
     *
     * @return array<string,mixed>
     */
    protected function getRequestContext(): array
    {
        $request = RequestFacade::instance();
        $payload = $request->all();

        $sanitized = $this->sanitizePayload($payload);

        return [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $sanitized,
        ];
    }

    /**
     * Sanitize sensitive fields from the payload.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'authorization'
        ];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            } elseif (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
                $payload[$key] = '[REDACTED]';
            }
        }

        return $payload;
    }
}

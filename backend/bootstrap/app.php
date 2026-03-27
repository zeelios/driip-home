<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1/webhooks')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->api(prepend: [\App\Http\Middleware\SanitizeUserInput::class]);

        $middleware->alias([
            'verify.courier.webhook' => \App\Http\Middleware\VerifyCourierWebhookSignature::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }
        });

        // Catch all other exceptions for API requests to ensure JSON response with request_code
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $code = 'API_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                report($e);

                Log::channel('single')->error('Unhandled API exception.', [
                    'request_code' => $code,
                    'method' => $request->method(),
                    'uri' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_id' => $request->user()?->id,
                    'exception' => $e,
                ]);

                return response()->json([
                    'success' => false,
                    'request_code' => $code,
                    'message' => $e->getMessage() ?: 'An unexpected error occurred.',
                ], 500);
            }
        });
    })->create();

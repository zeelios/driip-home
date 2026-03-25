<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces ownership-based access control for resources.
 *
 * Users without global permissions can only access resources they own.
 * Ownership is determined by comparing the user's ID to specified model fields.
 *
 * Usage in controller:
 *   new Middleware(OwnershipMiddleware::class . ':sales_rep_id,assigned_to', only: ['show', 'update'])
 */
class OwnershipMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$ownershipFields  Comma-separated list of fields to check (e.g., 'sales_rep_id,assigned_to')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$ownershipFields): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'request_code' => 'OWNERSHIP_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'message' => 'Authentication required.',
            ], 401);
        }

        // If user has global permission, skip ownership check
        $resource = $this->getResourceFromFields($ownershipFields);
        $globalPermission = "{$resource}.view";
        $managePermission = "{$resource}.manage";

        if ($user->hasAnyPermission([$globalPermission, $managePermission, "{$resource}.view.all"])) {
            return $next($request);
        }

        // Check ownership for specific resource
        $resourceId = $request->route($resource) ?? $request->route('id');

        if ($resourceId !== null) {
            $model = $this->getModel($resource, $resourceId);

            if ($model === null) {
                return response()->json([
                    'success' => false,
                    'request_code' => 'OWNERSHIP_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                    'message' => 'Resource not found.',
                ], 404);
            }

            // Check if user owns this resource via any of the specified fields
            $ownsResource = false;
            $fields = $this->parseOwnershipFields($ownershipFields);

            foreach ($fields as $field) {
                if ($model->{$field} === $user->id) {
                    $ownsResource = true;
                    break;
                }
            }

            if (!$ownsResource) {
                $action = $this->getActionName($request, $resource);
                $code = "{$action}_" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                return response()->json([
                    'success' => false,
                    'request_code' => $code,
                    'message' => 'You can only access resources you own.',
                ], 403);
            }
        }

        // For index/create operations without specific resource ID
        // Check if user has at least view.own permission
        $ownPermission = "{$resource}.view.own";
        if (!$user->hasAnyPermission([$ownPermission, $globalPermission, $managePermission])) {
            return response()->json([
                'success' => false,
                'request_code' => 'LIST_' . strtoupper($resource) . '_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Parse ownership fields from middleware parameter.
     *
     * @param  array<string>  $fields
     * @return array<string>
     */
    protected function parseOwnershipFields(array $fields): array
    {
        $parsed = [];
        foreach ($fields as $field) {
            // Handle comma-separated fields in single parameter
            $parts = explode(',', $field);
            foreach ($parts as $part) {
                $parsed[] = trim($part);
            }
        }
        return $parsed;
    }

    /**
     * Get the resource name from ownership fields.
     *
     * @param  array<string>  $fields
     * @return string
     */
    protected function getResourceFromFields(array $fields): string
    {
        // Default resource detection based on field names
        $field = $fields[0] ?? '';

        return match (true) {
            str_contains($field, 'sales_rep') => 'orders',
            str_contains($field, 'assigned_to') => 'orders',
            str_contains($field, 'packed_by') => 'orders',
            str_contains($field, 'user_id') => 'users',
            str_contains($field, 'staff_id') => 'staff',
            str_contains($field, 'created_by') => 'resources',
            default => 'resources',
        };
    }

    /**
     * Get the model instance for the resource.
     *
     * @param  string  $resource
     * @param  string  $resourceId
     * @return Model|null
     */
    protected function getModel(string $resource, string $resourceId): ?Model
    {
        $modelClass = match ($resource) {
            'orders' => \App\Domain\Order\Models\Order::class,
            'users', 'staff' => \App\Domain\Staff\Models\User::class,
            'customers' => \App\Domain\Customer\Models\Customer::class,
            'products' => \App\Domain\Product\Models\Product::class,
            'coupons' => \App\Domain\Coupon\Models\Coupon::class,
            'inventory' => \App\Domain\Inventory\Models\Inventory::class,
            default => null,
        };

        if ($modelClass === null) {
            return null;
        }

        return $modelClass::find($resourceId);
    }

    /**
     * Get action name from request for error code generation.
     *
     * @param  Request  $request
     * @param  string   $resource
     * @return string
     */
    protected function getActionName(Request $request, string $resource): string
    {
        $method = strtoupper($request->method());
        $resourceSingular = strtoupper(rtrim($resource, 's'));

        return match ($method) {
            'GET' => "VIEW_{$resourceSingular}",
            'POST' => "CREATE_{$resourceSingular}",
            'PUT', 'PATCH' => "UPDATE_{$resourceSingular}",
            'DELETE' => "DELETE_{$resourceSingular}",
            default => "ACCESS_{$resourceSingular}",
        };
    }
}

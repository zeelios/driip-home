<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Staff\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces role hierarchy - prevents lower-level roles from modifying higher-level roles.
 *
 * Hierarchy (low to high):
 * 1. warehouse-staff
 * 2. sales-staff
 * 3. manager
 * 4. admin
 * 5. super-admin (untouchable by anyone)
 *
 * This middleware should be applied to staff update/delete operations.
 */
class RoleHierarchyMiddleware
{
    /**
     * Role hierarchy levels. Higher number = higher rank.
     */
    protected const ROLE_LEVELS = [
        'warehouse-staff' => 1,
        'sales-staff'     => 2,
        'manager'         => 3,
        'admin'           => 4,
        'super-admin'     => 5,
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUser = $request->user();

        if ($currentUser === null) {
            return response()->json([
                'success' => false,
                'request_code' => 'ROLE_HIERARCHY_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
                'message' => 'Authentication required.',
            ], 401);
        }

        $currentUserLevel = $this->getRoleLevel($currentUser);

        // Super-admins can do anything (they're at the top)
        if ($currentUserLevel >= 5) {
            return $next($request);
        }

        // Try to get the target user from route parameter
        $targetUserId = $request->route('staff') ?? $request->route('user') ?? $request->route('id');

        if ($targetUserId !== null) {
            $targetUser = User::find($targetUserId);

            if ($targetUser !== null) {
                $targetUserLevel = $this->getRoleLevel($targetUser);

                // Cannot modify users with equal or higher role level
                if ($targetUserLevel >= $currentUserLevel) {
                    $action = strtoupper($request->method()) === 'DELETE' ? 'DELETE' : 'UPDATE';
                    $code = "{$action}_STAFF_" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

                    return response()->json([
                        'success' => false,
                        'request_code' => $code,
                        'message' => 'You cannot modify users with equal or higher role level than yours.',
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    /**
     * Get the role level for a user.
     *
     * @param  User  $user
     * @return int
     */
    protected function getRoleLevel(User $user): int
    {
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
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Staff\Models\User;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Staff\StaffResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handle authentication for staff accounts.
 *
 * Uses Laravel Sanctum personal access tokens.
 * All tokens are named by device/context for auditability.
 */
class AuthController extends BaseApiController
{
    /**
     * Authenticate a staff member and issue a Sanctum token.
     *
     * Validates credentials, checks account status, and returns a
     * plain-text token alongside the authenticated staff resource.
     *
     * @param  Request  $request
     * @return JsonResponse  Contains token + staff resource on success.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email'    => ['required', 'email'],
                'password' => ['required', 'string'],
                'device'   => ['nullable', 'string', 'max:100'],
            ]);

            $user = User::where('email', $data['email'])->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                return ErrorResource::fromException('Invalid credentials.', 'AUTH_LOGIN')
                    ->response()
                    ->setStatusCode(401);
            }

            if (!$user->isActive()) {
                return ErrorResource::fromException('Account is inactive.', 'AUTH_LOGIN')
                    ->response()
                    ->setStatusCode(403);
            }

            $token = $user->createToken($data['device'] ?? 'api')->plainTextToken;

            return response()->json([
                'success' => true,
                'token'   => $token,
                'data'    => StaffResource::make($user->load('roles')),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'AUTH_LOGIN');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'AUTH_LOGIN');
        }
    }

    /**
     * Revoke the current access token (logout).
     *
     * Deletes the token used for this request, effectively ending the session.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    /**
     * Return the currently authenticated staff member.
     *
     * Loads roles and profile relationships before returning the resource.
     *
     * @param  Request  $request
     * @return StaffResource
     */
    public function me(Request $request): StaffResource
    {
        return StaffResource::make($request->user()->load(['roles', 'profile']));
    }
}

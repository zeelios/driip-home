<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Staff\Models\User;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\Staff\StaffResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Handle authentication for staff accounts.
 *
 * Uses Laravel Sanctum SPA session authentication.
 */
class AuthController extends BaseApiController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     *
     * Auth endpoints are public (no auth required), except logout and me.
     *
     * @return array<\Illuminate\Routing\Controllers\Middleware>
     */
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth:sanctum', only: ['logout', 'me']),
        ];
    }

    /**
     * Authenticate a staff member using the session guard.
     *
     * @param  Request  $request
     * @return JsonResponse  Contains the authenticated staff resource on success.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
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

            Auth::guard('web')->login($user);
            $request->session()->regenerate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'data' => StaffResource::make($user->load('roles')),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'AUTH_LOGIN');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'AUTH_LOGIN');
        }
    }

    /**
     * Send a password reset link to the given email address.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
            ]);

            Password::sendResetLink(['email' => $data['email']]);

            // Always return success to avoid email enumeration.
            return response()->json([
                'success' => true,
                'message' => 'If that email exists, a reset link has been sent.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'AUTH_FORGOT_PASSWORD');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'AUTH_FORGOT_PASSWORD');
        }
    }

    /**
     * Reset a user's password using a valid reset token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'password_confirmation' => ['required', 'string'],
            ]);

            $status = Password::reset(
                $data,
                function (User $user, string $password) {
                    $user->forceFill(['password' => Hash::make($password)])->save();
                    $user->tokens()->delete();
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                return ErrorResource::fromException(__($status), 'AUTH_RESET_PASSWORD')
                    ->response()
                    ->setStatusCode(422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset.',
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e, 'AUTH_RESET_PASSWORD');
        } catch (\Throwable $e) {
            return $this->serverError($e, 'AUTH_RESET_PASSWORD');
        }
    }

    /**
     * Invalidate the current authenticated session.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

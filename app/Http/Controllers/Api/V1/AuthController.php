<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use App\Http\Resources\Api\V1\AuthResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;

class AuthController extends BaseApiController
{
    public function __construct(
        protected readonly AuthService $authService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register(
            $request->validated(),
            $request->input('device_name')
        );

        return $this->createdResponse(
            data: new AuthResource($result['user'], $result['token']),
            message: __('message.auth.registered')
        );
    }

    /**
     * Authenticate a user and issue API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->input('email'),
            $request->input('password'),
            $request->input('device_name')
        );

        return $this->successResponse(
            data: new AuthResource($result['user'], $result['token']),
            message: __('message.auth.logged_in')
        );
    }

    /**
     * Revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logout($user);

        return $this->successResponse(
            message: __('message.auth.logged_out')
        );
    }

    /**
     * Revoke all access tokens across all devices.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logoutAll($user);

        return $this->successResponse(
            message: __('message.auth.logged_out_all')
        );
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $this->authService->getProfile($user);

        return $this->successResponse(
            data: new UserResource($profile),
            message: __('message.auth.profile_retrieved')
        );
    }

    /**
     * Update authenticated user profile.
     */
    public function updateMe(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $updatedUser = $this->authService->updateProfile($user, $request->validated());

        return $this->successResponse(
            data: new UserResource($updatedUser),
            message: __('message.auth.profile_updated')
        );
    }

    /**
     * Change user password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->changePassword($user, $request->input('password'));

        return $this->successResponse(
            message: __('message.auth.password_changed')
        );
    }

    /**
     * Send password reset link to email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendResetLink($request->input('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(message: __($status))
            : $this->errorResponse(message: __($status));
    }

    /**
     * Reset password using token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string, password_confirmation: string, token: string} $credentials */
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        $status = $this->authService->resetPassword($credentials);

        return $status === Password::PASSWORD_RESET
            ? $this->successResponse(message: __($status))
            : $this->errorResponse(message: __($status));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class PasswordController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Send password reset link to user's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->forgotPassword($request->validated());

        return $this->success(null, $message);
    }

    /**
     * Reset user's password using reset token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $message = $this->authService->resetPassword($request->validated());

        return $this->success(null, $message);
    }
}

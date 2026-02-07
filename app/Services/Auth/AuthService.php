<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Authenticate user and generate token.
     *
     * @param array $credentials
     * @return array
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        Log::debug("Credentials: ", $credentials);

        // Attempt authentication using Auth facade
        if (
            !Auth::attempt([
                "email" => $credentials["email"],
                "password" => $credentials["password"],
            ])
        ) {
            throw ValidationException::withMessages([
                "email" => ["The provided credentials are incorrect."],
            ]);
        }

        // Get authenticated user
        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            // Logout the authenticated user
            Auth::logout();

            throw ValidationException::withMessages([
                "email" => ["Your account has been deactivated."],
            ]);
        }

        // Update last login timestamp
        $user->update(["last_login_at" => now()]);

        // Generate Sanctum token
        $token = $user->createToken("auth-token")->plainTextToken;

        return [
            "user" => $user->load([
                "company",
                "role.permissions",
                "permissions",
            ]),
            "token" => $token,
        ];
    }

    /**
     * Logout user and revoke token.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        // Revoke all tokens for the user
        $user->tokens()->delete();
    }

    /**
     * Send password reset link to user's email.
     *
     * @param array $data
     * @return string
     * @throws ValidationException
     */
    public function forgotPassword(array $data): string
    {
        $status = Password::sendResetLink(["email" => $data["email"]]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                "email" => [__($status)],
            ]);
        }

        return __($status);
    }

    /**
     * Reset user's password using reset token.
     *
     * @param array $data
     * @return string
     * @throws ValidationException
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            [
                "email" => $data["email"],
                "password" => $data["password"],
                "token" => $data["token"],
            ],
            function (User $user, string $password) {
                $user
                    ->forceFill([
                        "password" => Hash::make($password),
                    ])
                    ->setRememberToken(Str::random(60));

                $user->save();

                // Revoke all existing tokens to force re-login
                $user->tokens()->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                "email" => [__($status)],
            ]);
        }

        return __($status);
    }
}

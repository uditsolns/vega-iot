<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Get user profile with relationships.
     *
     * @param User $user
     * @return User
     */
    public function getProfile(User $user): User
    {
        return $user->load([
            'company',
            'role.permissions',
            'permissions',
            'areaAccess',
        ]);
    }

    /**
     * Update user profile.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        // Only allow updating specific fields
        $allowedFields = ['first_name', 'last_name', 'phone'];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $user->update($updateData);

        return $user->fresh();
    }

    /**
     * Change user password.
     *
     * @param User $user
     * @param array $data
     * @return void
     * @throws ValidationException
     */
    public function changePassword(User $user, array $data): void
    {
        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        // Revoke all existing tokens to force re-login
        $user->tokens()->delete();
    }
}

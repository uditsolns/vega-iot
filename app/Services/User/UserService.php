<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class UserService
{
    /**
     * Get paginated list of users.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(User::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("email"),
                AllowedFilter::partial("first_name"),
                AllowedFilter::partial("last_name"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("role_id"),
                AllowedFilter::exact("company_id"),
            ])
            ->allowedSorts([
                "email",
                "first_name",
                "created_at",
                "last_login_at",
            ])
            ->allowedIncludes(["company", "role", "permissions"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        // Generate random password if not provided
        if (!isset($data["password"])) {
            $data["password"] = Str::random();
        }

        $data["password"] = Hash::make($data["password"]);

        $user = User::create($data);

        return $user;
    }

    /**
     * Update a user.
     */
    public function update(User $user, array $data): User
    {
        // If password is provided, hash it
        if (isset($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Delete a user (soft delete).
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * Change user's role.
     */
    public function changeRole(User $user, int $roleId): User
    {
        $oldRole = $user->role;
        $user->update(["role_id" => $roleId]);
        $user = $user->fresh(["role"]);

        // Audit log
        activity("user")
            ->event("role_changed")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "old_role_id" => $oldRole->id,
                "new_role_id" => $user->role->id,
            ])
            ->log("Changed role from \"{$oldRole->name}\" to \"{$user->role->name}\" for user \"{$user->email}\"");

        return $user;
    }

    /**
     * Reset user's password.
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $user->update(["password" => Hash::make($newPassword)]);

        // Revoke all tokens to force re-login
        $user->tokens()->delete();
    }

    /**
     * Activate a user.
     */
    public function activate(User $user): User
    {
        $user->update(["is_active" => true]);

        // Audit log
        activity("user")
            ->event("activated")
            ->performedOn($user)
            ->withProperties(["user_id" => $user->id])
            ->log("Activated user \"{$user->email}\"");

        return $user->fresh();
    }

    /**
     * Deactivate a user.
     */
    public function deactivate(User $user): User
    {
        $user->update(["is_active" => false]);

        // Revoke all tokens
        $user->tokens()->delete();

        // Audit log
        activity("user")
            ->event("deactivated")
            ->performedOn($user)
            ->withProperties(["user_id" => $user->id])
            ->log("Deactivated user \"{$user->email}\"");

        return $user->fresh();
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user): User
    {
        $user->restore();

        // Audit log
        activity("user")
            ->event("restored")
            ->performedOn($user)
            ->withProperties(["user_id" => $user->id])
            ->log("Restored user \"{$user->email}\"");

        return $user->fresh();
    }
}

<?php

namespace App\Services\User;

use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class UserService
{
    public function __construct(private AuditService $auditService) {}
    /**
     * Get paginated list of users.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
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
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User
    {
        // Generate random password if not provided
        if (!isset($data["password"])) {
            $data["password"] = Str::random();
        }

        $data["password"] = Hash::make($data["password"]);

        $user = User::create($data);

        // Audit log
        $this->auditService->log("user.created", User::class, $user);

        return $user;
    }

    /**
     * Update a user.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User
    {
        // If password is provided, hash it
        if (isset($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }

        $user->update($data);

        // Audit log
        $this->auditService->log("user.updated", User::class, $user);

        return $user->fresh();
    }

    /**
     * Delete a user (soft delete).
     *
     * @param User $user
     * @return void
     */
    public function delete(User $user): void
    {
        $user->delete();

        // Audit log
        $this->auditService->log("user.deleted", User::class, $user);
    }

    /**
     * Change user's role.
     *
     * @param User $user
     * @param int $roleId
     * @return User
     */
    public function changeRole(User $user, int $roleId): User
    {
        $user->update(["role_id" => $roleId]);

        // Audit log
        $this->auditService->log("user.role_changed", User::class, $user);

        return $user->fresh(["role"]);
    }

    /**
     * Reset user's password.
     *
     * @param User $user
     * @param string $newPassword
     * @return void
     */
    public function resetPassword(User $user, string $newPassword): void
    {
        $user->update(["password" => Hash::make($newPassword)]);

        // Revoke all tokens to force re-login
        $user->tokens()->delete();
    }

    /**
     * Activate a user.
     *
     * @param User $user
     * @return User
     */
    public function activate(User $user): User
    {
        $user->update(["is_active" => true]);

        // Audit log
        $this->auditService->log("user.activated", User::class, $user);

        return $user->fresh();
    }

    /**
     * Deactivate a user.
     *
     * @param User $user
     * @return User
     */
    public function deactivate(User $user): User
    {
        $user->update(["is_active" => false]);

        // Revoke all tokens
        $user->tokens()->delete();

        // Audit log
        $this->auditService->log("user.deactivated", User::class, $user);

        return $user->fresh();
    }

    /**
     * Restore a soft-deleted user.
     *
     * @param User $user
     * @return User
     */
    public function restore(User $user): User
    {
        $user->restore();

        return $user->fresh();
    }
}

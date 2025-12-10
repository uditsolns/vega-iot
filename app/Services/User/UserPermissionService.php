<?php

namespace App\Services\User;

use App\Models\User;

class UserPermissionService
{
    /**
     * Grant a permission to a user.
     *
     * @param User $user
     * @param int $permissionId
     * @param int|null $grantedBy
     * @return void
     */
    public function grantPermission(User $user, int $permissionId, ?int $grantedBy = null): void
    {
        $user->permissions()->syncWithoutDetaching([
            $permissionId => ['granted_by' => $grantedBy],
        ]);
    }

    /**
     * Revoke a permission from a user.
     *
     * @param User $user
     * @param int $permissionId
     * @return void
     */
    public function revokePermission(User $user, int $permissionId): void
    {
        $user->permissions()->detach($permissionId);
    }

    /**
     * Sync user permissions (replace all).
     *
     * @param User $user
     * @param array $permissionIds
     * @param int|null $grantedBy
     * @return void
     */
    public function syncPermissions(User $user, array $permissionIds, ?int $grantedBy = null): void
    {
        $syncData = [];
        foreach ($permissionIds as $permissionId) {
            $syncData[$permissionId] = ['granted_by' => $grantedBy];
        }

        $user->permissions()->sync($syncData);
    }
}

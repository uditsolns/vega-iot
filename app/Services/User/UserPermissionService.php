<?php

namespace App\Services\User;

use App\Models\Permission;
use App\Models\User;
use Auth;

class UserPermissionService
{

    /**
     * Grant a permission to a user.
     */
    public function grantPermission(User $user, int $permissionId, ?int $grantedBy = null): void
    {
        $user->permissions()->syncWithoutDetaching([
            $permissionId => ['granted_by' => $grantedBy or Auth::user()->id],
        ]);

        $permission = Permission::find($permissionId);

        activity("user")
            ->event("granted_permission")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "permission_id" => $permission->id,
            ])
            ->log("Granted permission \"{$permission->name}\" to user \"{$user->email}\"");
    }

    /**
     * Revoke a permission from a user.
     */
    public function revokePermission(User $user, int $permissionId): void
    {
        $permission = Permission::find($permissionId);
        $user->permissions()->detach($permissionId);

        activity("user")
            ->event("revoked_permission")
            ->performedOn($user)
            ->withProperties([
                "user_id" => $user->id,
                "permission_id" => $permission->id,
            ])
            ->log("Revoked permission \"{$permission->name}\" to user \"{$user->email}\"");
    }

    /**
     * Sync user permissions (replace all).
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

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\GrantPermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use App\Services\User\UserPermissionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserPermissionService $userPermissionService,
    ) {}

    public function index(Request $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        $permissions = $user->permissions;

        return $this->success([
            "permissions" => PermissionResource::collection($permissions),
        ]);
    }

    public function grantPermission(Request $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        $validated = $request->validate([
            "permission_id" => ["required", "integer", "exists:permissions,id"],
        ]);

        $this->userPermissionService->grantPermission(
            $user,
            $validated["permission_id"],
        );

        return $this->success(null, "Permission granted successfully");
    }

    public function revokePermission(
        User $user,
        int $permissionId,
    ): JsonResponse {
        $this->authorize("update", $user);

        $this->userPermissionService->revokePermission($user, $permissionId);

        return $this->success(null, "Permission revoked successfully");
    }

    public function syncPermissions(
        GrantPermissionsRequest $request,
        User $user,
    ): JsonResponse {
        $this->authorize("update", $user);

        $this->userPermissionService->syncPermissions(
            $user,
            $request->validated()["permission_ids"],
        );

        return $this->success(null, "Permissions synced successfully");
    }
}

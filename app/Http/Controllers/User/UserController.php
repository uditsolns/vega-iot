<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeRoleRequest;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UserService $userService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", User::class);

        $users = $this->userService->list($request->all(), $request->user());

        return $this->collection(UserResource::collection($users));
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize("create", User::class);

        if (($request->validated("role_id") == 1) && (!$request->user()->isSuperAdmin())) {
            return $this->unauthorized();
        }

        $user = $this->userService->create($request->validated());

        return $this->created(
            new UserResource($user),
            "User created successfully",
        );
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->authorize("view", $user);

        $user->loadMissing(["company", "role.permissions", "permissions", "areaAccess"]);

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        $user = $this->userService->update($user, $request->validated());

        return $this->success(
            new UserResource($user),
            "User updated successfully",
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize("delete", $user);

        $this->userService->delete($user);

        return $this->success(null, "User deleted successfully");
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        $user = $this->userService->activate($user);

        return $this->success(
            new UserResource($user),
            "User activated successfully",
        );
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        $user = $this->userService->deactivate($user);

        return $this->success(
            new UserResource($user),
            "User deactivated successfully",
        );
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);

        $this->authorize("update", $user);

        $user = $this->userService->restore($user);

        return $this->success(
            new UserResource($user),
            "User restored successfully",
        );
    }

    public function changeRole(
        ChangeRoleRequest $request,
        User $user,
    ): JsonResponse {
        $this->authorize("changeRole", $user);

        $user = $this->userService->changeRole(
            $user,
            $request->validated()["role_id"],
        );

        return $this->success(
            new UserResource($user),
            "User role changed successfully",
        );
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize("update", $user);

        // TODO: Implement in future module with notification system
        return $this->success(
            null,
            "TODO: Password reset email functionality will be implemented in notifications module",
        );
    }

    public function resendInvite(Request $request, User $user): JsonResponse
    {
        $this->authorize("create", User::class);

        // TODO: Implement in future module with notification system
        return $this->success(
            null,
            "TODO: Invite email functionality will be implemented in notifications module",
        );
    }

    public function activity(Request $request, User $user): JsonResponse
    {
        $this->authorize("view", $user);

        // TODO: Implement in future module with audit/activity logging
        return $this->success(
            ["activities" => []],
            "TODO: User activity tracking will be implemented in audit module",
        );
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize("viewAny", User::class);

        // TODO: Implement in future module with export functionality
        return $this->success(
            null,
            "TODO: User export functionality will be implemented in reports module",
        );
    }
}

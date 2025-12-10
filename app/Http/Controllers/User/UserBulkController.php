<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\BulkUserRequest;
use App\Models\User;
use App\Services\User\AreaAccessService;
use App\Services\User\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserBulkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService       $userService,
        private readonly AreaAccessService $areaAccessService,
    ) {}

    public function bulkActivate(BulkUserRequest $request): JsonResponse
    {
        $userIds = $request->validated()["user_ids"];
        $currentUser = $request->user();

        $results = $this->processBulkOperation(
            $currentUser,
            $userIds,
            function ($user) {
                $this->authorize("update", $user);
                $this->userService->activate($user);
            },
        );

        return $this->bulkResponse($results, "Users activated successfully");
    }

    public function bulkDeactivate(BulkUserRequest $request): JsonResponse
    {
        $userIds = $request->validated()["user_ids"];
        $currentUser = $request->user();

        $results = $this->processBulkOperation(
            $currentUser,
            $userIds,
            function ($user) {
                $this->authorize("update", $user);
                $this->userService->deactivate($user);
            },
        );

        return $this->bulkResponse($results, "Users deactivated successfully");
    }

    public function bulkDelete(BulkUserRequest $request): JsonResponse
    {
        $userIds = $request->validated()["user_ids"];
        $currentUser = $request->user();

        $results = $this->processBulkOperation(
            $currentUser,
            $userIds,
            function ($user) {
                $this->authorize("delete", $user);
                $this->userService->delete($user);
            },
        );

        return $this->bulkResponse($results, "Users deleted successfully");
    }

    public function bulkChangeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "user_ids" => ["required", "array", "min:1"],
            "user_ids.*" => ["required", "integer", "exists:users,id"],
            "role_id" => ["required", "integer", "exists:roles,id"],
        ]);

        $userIds = $validated["user_ids"];
        $roleId = $validated["role_id"];
        $currentUser = $request->user();

        $results = $this->processBulkOperation(
            $currentUser,
            $userIds,
            function ($user) use ($roleId) {
                $this->authorize("changeRole", $user);
                $this->userService->changeRole($user, $roleId);
            },
        );

        return $this->bulkResponse($results, "User roles changed successfully");
    }

    public function bulkGrantAreas(Request $request): JsonResponse
    {
        $this->authorize("viewAny", User::class);

        $validated = $request->validate([
            "user_ids" => ["required", "array", "min:1"],
            "user_ids.*" => ["required", "integer", "exists:users,id"],
            "area_ids" => ["required", "array", "min:1"],
            "area_ids.*" => ["required", "integer", "exists:areas,id"],
        ]);

        $userIds = $validated["user_ids"];
        $areaIds = $validated["area_ids"];
        $grantedBy = $request->user()->id;

        // Authorization check for each user
        $currentUser = $request->user();
        $users = User::forUser($currentUser)->whereIn("id", $userIds)->get();

        foreach ($users as $user) {
            $this->authorize("assignAreas", $user);
        }

        $results = $this->areaAccessService->bulkGrantAreas(
            $userIds,
            $areaIds,
            $grantedBy,
        );

        return $this->bulkResponse(
            $results,
            "Area access granted to users successfully",
        );
    }

    public function bulkRevokeAreas(Request $request): JsonResponse
    {
        $this->authorize("viewAny", User::class);

        $validated = $request->validate([
            "user_ids" => ["required", "array", "min:1"],
            "user_ids.*" => ["required", "integer", "exists:users,id"],
            "area_ids" => ["required", "array", "min:1"],
            "area_ids.*" => ["required", "integer", "exists:areas,id"],
        ]);

        $userIds = $validated["user_ids"];
        $areaIds = $validated["area_ids"];

        // Authorization check for each user
        $currentUser = $request->user();
        $users = User::forUser($currentUser)->whereIn("id", $userIds)->get();

        foreach ($users as $user) {
            $this->authorize("assignAreas", $user);
        }

        $results = $this->areaAccessService->bulkRevokeAreas(
            $userIds,
            $areaIds,
        );

        return $this->bulkResponse(
            $results,
            "Area access revoked from users successfully",
        );
    }

    /**
     * Process bulk operation and collect results
     */
    private function processBulkOperation(
        User $currentUser,
        array $userIds,
        callable $operation,
    ): array {
        $query = User::query()->forUser($currentUser);
        $users = $query->whereIn("id", $userIds)->get();

        $successCount = 0;
        $failures = [];

        foreach ($users as $user) {
            try {
                $operation($user);
                $successCount++;
            } catch (\Exception $e) {
                $failures[] = [
                    "id" => $user->id,
                    "error" => $e->getMessage(),
                ];
            }
        }

        return [
            "success_count" => $successCount,
            "failed_count" => count($failures),
            "failures" => $failures,
        ];
    }
}

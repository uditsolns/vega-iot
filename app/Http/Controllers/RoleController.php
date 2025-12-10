<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\CloneRoleRequest;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Services\User\RoleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roleService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize("viewAny", Role::class);

        $roles = $this->roleService->list($request->all(), $request->user());

        return $this->collection(RoleResource::collection($roles));
    }

    public function store(CreateRoleRequest $request): JsonResponse
    {
        $this->authorize("create", Role::class);

        $data = $request->validated();

        // Add company_id if not super admin
        if (!$request->user()->isSuperAdmin()) {
            $data["company_id"] = $request->user()->company_id;
        }

        $role = $this->roleService->create($data);

        return $this->created(
            new RoleResource($role),
            "Role created successfully",
        );
    }

    public function show(Request $request, Role $role): JsonResponse
    {
        $this->authorize("view", $role);

        $role->loadMissing("permissions");

        return $this->success(new RoleResource($role));
    }

    /**
     * @throws Exception
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize("update", $role);

        $role = $this->roleService->update($role, $request->validated());

        return $this->success(
            new RoleResource($role),
            "Role updated successfully",
        );
    }

    /**
     * @throws Exception
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        $this->authorize("delete", $role);

        $this->roleService->delete($role);

        return $this->success(null, "Role deleted successfully");
    }

    public function clone(CloneRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize("create", Role::class);

        $newRole = $this->roleService->clone($role, $request->validated());

        return $this->created(
            new RoleResource($newRole),
            "Role cloned successfully",
        );
    }

    public function users(Request $request, Role $role): JsonResponse
    {
        $this->authorize("view", $role);

        $users = $role
            ->users()
            ->forUser($request->user())
            ->with("company")
            ->paginate($request->input("per_page", 20));

        return $this->collection(UserResource::collection($users));
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\UserResource;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    /**
     * Get current user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->profileService->getProfile($request->user());

        return $this->success(new UserResource($user));
    }

    /**
     * Update current user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile(
            $request->user(),
            $request->validated(),
        );

        return $this->success(
            new UserResource($user),
            "Profile updated successfully",
        );
    }

    /**
     * Change current user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->profileService->changePassword(
            $request->user(),
            $request->validated(),
        );

        return $this->success(null, "Password changed successfully");
    }

    /**
     * Get current user's effective permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        $permissions = $request->user()->getAllPermissions();

        return $this->success(PermissionResource::collection($permissions));
    }

    /**
     * Get current user's accessible areas.
     * Note: Will return empty array until Phase 2 (Hierarchy module) is implemented.
     */
    public function areas(Request $request): JsonResponse
    {
        // User's allowed areas loaded by PrepareUserContext middleware
        $areaIds = $request->user()->allowedAreas ?? [];

        return $this->success([
            "area_ids" => $areaIds,
            "has_restrictions" =>
                $request->user()->hasAreaRestrictions ?? false,
        ]);
    }
}

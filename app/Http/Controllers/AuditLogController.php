<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\UnauthorizedException;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Get paginated list of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('audit.view')) {
            throw new UnauthorizedException();
        }

        $logs = $this->auditService->list($request->all(), $request->user());

        return $this->collection(AuditLogResource::collection($logs));
    }

    /**
     * Get user's activity history
     */
    public function userActivity(User $user, Request $request): JsonResponse
    {
        // Authorize: super admin or viewing own activity
        if (!$request->user()->isSuperAdmin() && $request->user()->id !== $user->id) {
            return $this->error('Unauthorized', 403);
        }

        // Validate days parameter
        $validator = Validator::make($request->all(), [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $days = $request->input('days', 30);
        $activity = $this->auditService->getUserActivity($user->id, $days);

        return $this->success(AuditLogResource::collection($activity));
    }

    /**
     * Get resource history
     */
    public function resourceHistory(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('audit.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate required parameters
        $validator = Validator::make($request->all(), [
            'resource_type' => ['required', 'string'],
            'resource_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $resourceType = $request->input('resource_type');
        $resourceId = $request->input('resource_id');

        $history = $this->auditService->getResourceHistory($resourceType, $resourceId);

        return $this->success(AuditLogResource::collection($history));
    }
}

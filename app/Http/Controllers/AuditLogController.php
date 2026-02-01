<?php

namespace App\Http\Controllers;

use App\Services\Audit\AuditService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuditService $activityQueryService,
    ) {}

    /**
     * Get paginated list of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('audit.view')) {
            throw new UnauthorizedException("Unauthorized", 403);
        }

        $logs = $this->activityQueryService->list($request->all(), $request->user());

        return $this->success($logs);
    }
}

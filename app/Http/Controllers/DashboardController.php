<?php

namespace App\Http\Controllers;

use App\Http\Resources\AlertResource;
use App\Http\Resources\AuditLogResource;
use App\Services\Dashboard\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Get dashboard overview statistics
     */
    public function overview(Request $request): JsonResponse
    {
        // Any authenticated user can access overview
        $overview = $this->dashboardService->getOverview($request->user());

        return $this->success($overview);
    }

    /**
     * Get device status breakdown
     */
    public function deviceStatus(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('devices.view')) {
            return $this->error('Unauthorized', 403);
        }

        $deviceStatus = $this->dashboardService->getDeviceStatus($request->user());

        return $this->success($deviceStatus);
    }

    /**
     * Get active alerts
     */
    public function activeAlerts(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('alerts.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate limit parameter
        $validator = Validator::make($request->all(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $limit = $request->input('limit', 10);
        $alerts = $this->dashboardService->getActiveAlerts($request->user(), $limit);

        return $this->success(AlertResource::collection($alerts));
    }

    /**
     * Get recent activity from audit logs
     */
    public function recentActivity(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('audit.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate limit parameter
        $validator = Validator::make($request->all(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $limit = $request->input('limit', 20);
        $activity = $this->dashboardService->getRecentActivity($request->user(), $limit);

        return $this->success(AuditLogResource::collection($activity));
    }

    /**
     * Get temperature trends for charting
     */
    public function temperatureTrends(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('readings.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate days parameter
        $validator = Validator::make($request->all(), [
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $days = $request->input('days', 7);
        $trends = $this->dashboardService->getTemperatureTrends($request->user(), $days);

        return $this->success([
            'days' => $days,
            'data' => $trends,
        ]);
    }

    /**
     * Get alert trends for charting
     */
    public function alertTrends(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('alerts.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate days parameter
        $validator = Validator::make($request->all(), [
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $days = $request->input('days', 7);
        $trends = $this->dashboardService->getAlertTrends($request->user(), $days);

        return $this->success([
            'days' => $days,
            'data' => $trends,
        ]);
    }

    /**
     * Get top devices by alert count
     */
    public function topDevicesByAlerts(Request $request): JsonResponse
    {
        if (!$request->user()->hasPermission('alerts.view')) {
            return $this->error('Unauthorized', 403);
        }

        // Validate limit parameter
        $validator = Validator::make($request->all(), [
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $limit = $request->input('limit', 5);
        $topDevices = $this->dashboardService->getTopDevicesByAlerts($request->user(), $limit);

        return $this->success($topDevices);
    }
}

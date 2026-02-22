<?php

namespace App\Http\Controllers;

use App\Http\Requests\Alert\AcknowledgeAlertRequest;
use App\Http\Requests\Alert\ListAlertsRequest;
use App\Http\Requests\Alert\ResolveAlertRequest;
use App\Http\Resources\AlertNotificationResource;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\Alert\AlertService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AlertService $alertService) {}

    /**
     * Get paginated list of alerts with filters
     */
    public function index(): JsonResponse
    {
        $this->authorize("viewAny", Alert::class);

        $alerts = $this->alertService->list(
            request()->all(),
            request()->user(),
        );

        return $this->collection(AlertResource::collection($alerts));
    }

    /**
     * Get a single alert by ID
     */
    public function show(Alert $alert): JsonResponse
    {
        $this->authorize("view", $alert);

        $alert = $this->alertService->show($alert);

        return $this->success(new AlertResource($alert), "Alert retrieved");
    }

    /**
     * Acknowledge a single alert
     */
    public function acknowledge(
        AcknowledgeAlertRequest $request,
        Alert $alert,
    ): JsonResponse {
        $this->authorize("acknowledge", $alert);

        $alert = $this->alertService->acknowledge(
            $alert,
            $request->user(),
            $request->validated("comment"),
        );

        return $this->success(new AlertResource($alert), "Alert acknowledged");
    }

    /**
     * Resolve a single alert
     */
    public function resolve(
        ResolveAlertRequest $request,
        Alert $alert,
    ): JsonResponse {
        $this->authorize("resolve", $alert);

        $alert = $this->alertService->resolve(
            $alert,
            $request->user(),
            $request->validated("comment"),
        );

        return $this->success(new AlertResource($alert), "Alert resolved");
    }
}

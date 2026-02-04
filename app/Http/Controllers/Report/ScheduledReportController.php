<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduledReport\CreateScheduledReportRequest;
use App\Http\Requests\ScheduledReport\UpdateScheduledReportRequest;
use App\Http\Resources\ScheduledReportExecutionResource;
use App\Http\Resources\ScheduledReportResource;
use App\Models\ScheduledReport;
use App\Services\ScheduledReport\ScheduledReportExecutionService;
use App\Services\ScheduledReport\ScheduledReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduledReportController extends Controller
{
    public function __construct(
        private readonly ScheduledReportService $service,
        private readonly ScheduledReportExecutionService $executionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ScheduledReport::class);

        $reports = $this->service->list($request->all(), $request->user());

        return $this->collection(ScheduledReportResource::collection($reports));
    }

    public function store(CreateScheduledReportRequest $request): JsonResponse
    {
        $scheduledReport = $this->service->create(
            $request->validated(),
            $request->user()
        );

        return $this->created(
            new ScheduledReportResource($scheduledReport),
            'Scheduled report created successfully'
        );
    }

    public function show(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $scheduledReport);

        $scheduledReport->load('devices', 'createdBy', 'executions');

        return $this->success(new ScheduledReportResource($scheduledReport));
    }

    public function update(
        UpdateScheduledReportRequest $request,
        ScheduledReport $scheduledReport
    ): JsonResponse {
        $this->authorize('update', $scheduledReport);

        $updated = $this->service->update($scheduledReport, $request->validated());

        return $this->success(
            new ScheduledReportResource($updated),
            'Scheduled report updated successfully'
        );
    }

    public function destroy(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('delete', $scheduledReport);

        $this->service->delete($scheduledReport);

        return $this->success(null, 'Scheduled report deleted successfully');
    }

    public function toggle(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('update', $scheduledReport);

        $updated = $this->service->toggle($scheduledReport);

        $message = $updated->is_active
            ? 'Scheduled report activated successfully'
            : 'Scheduled report deactivated successfully';

        return $this->success($updated, $message);
    }

    public function executions(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('view', $scheduledReport);

        $executions = $scheduledReport->executions()
            ->orderBy('executed_at', 'desc')
            ->paginate(20);

        return $this->collection(ScheduledReportExecutionResource::collection($executions));
    }

    public function testRun(ScheduledReport $scheduledReport): JsonResponse
    {
        $this->authorize('update', $scheduledReport);

        $execution = $this->executionService->execute($scheduledReport);

        return $this->success($execution, 'Test execution completed');
    }
}

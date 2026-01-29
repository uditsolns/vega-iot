<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CreateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(readonly private ReportService $reportService){}

    public function index(Request $request)
    {
        $this->authorize("viewAny", Report::class);

        $reports = $this->reportService->list(
            $request->all(),
            $request->user(),
        );

        return $this->collection(ReportResource::collection($reports));
    }

    public function store(CreateReportRequest $request)
    {
        $this->authorize("create", Report::class);

        $report = $this->reportService->create($request->validated());

        // TODO: generate report
        // TODO: send report to user's mail

        return $this->created(
            new ReportResource($report),
            "Report successfully created"
        );
    }
}

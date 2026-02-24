<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CreateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(readonly private ReportService $reportService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $reports = $this->reportService->list($request->all(), $request->user());

        return $this->collection(ReportResource::collection($reports));
    }

    public function store(CreateReportRequest $request): Response
    {
        $this->authorize('create', Report::class);

        $report  = $this->reportService->create($request->validated(), $request->user());
        $content = $this->reportService->generate($report);

        return response($content, 200, [
            'Content-Type'        => $report->contentType(),
            'Content-Disposition' => 'attachment; filename="' . $report->downloadFilename() . '"',
        ]);
    }

    public function download(Report $report): Response
    {
        $this->authorize('view', $report);

        $content = $this->reportService->generate($report);

        return response($content, 200, [
            'Content-Type'        => $report->contentType(),
            'Content-Disposition' => 'attachment; filename="' . $report->downloadFilename() . '"',
        ]);
    }
}

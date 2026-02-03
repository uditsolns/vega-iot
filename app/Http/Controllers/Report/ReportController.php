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

        $data = $request->validated();
        $data['generated_by'] = $request->user()->id;
        $data['company_id'] = $request->user()->company_id;

        // Create report record
        $report = $this->reportService->create($data);

        return $this->generate($report);
    }

    public function download(Report $report) {
        $this->authorize("viewAny", Report::class);

        return $this->generate($report);
    }

    public function generate(Report $report)
    {
        try {
            // Generate PDF file
            $pdfContent = $this->reportService->generateReport($report);

            // Return file download response
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $report->name . '.pdf"',
            ]);

        } catch (\Exception $e) {
            \Log::error('Report generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error(
                'Failed to generate report: ' . $e->getMessage(),
                500
            );
        }
    }
}

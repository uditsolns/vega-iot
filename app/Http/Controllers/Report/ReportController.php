<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CreateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        try {
            // Generate PDF file
            $filePath = $this->reportService->generateReport($report);

            // Return file download response
            return response()->download(
                Storage::path($filePath),
                basename($filePath),
                [
                    'Content-Type' => 'application/pdf',
                ]
            )->deleteFileAfterSend(false); // Keep file for records

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

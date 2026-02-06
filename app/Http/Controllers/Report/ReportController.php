<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\CreateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\MpdfException;

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

    /**
     * @throws MpdfException
     */
    public function store(CreateReportRequest $request): Response
    {
        $this->authorize("create", Report::class);

        // Create report record
        $report = $this->reportService->create(
            $request->validated(),
            $request->user(),
        );

        $pdfContent = $this->reportService->generateAndSend($report);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $report->name . '.pdf"',
        ]);
    }

    /**
     * @throws MpdfException
     */
    public function download(Report $report) {
        $this->authorize("viewAny", Report::class);

        return $this->reportService->generate($report);
    }
}

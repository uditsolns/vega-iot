<?php
// app/Http/Controllers/AuditReport/AuditReportController.php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditReport\CreateAuditReportRequest;
use App\Models\AuditReport;
use App\Services\Audit\AuditReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mpdf\MpdfException;

class AuditReportController extends Controller
{
    public function __construct(
        private readonly AuditReportService $auditReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditReport::class);

        $reports = $this->auditReportService->list(
            $request->all(),
            $request->user()
        );

        return $this->success($reports);
    }

    /**
     * @throws MpdfException
     */
    public function store(CreateAuditReportRequest $request): Response
    {
//        $this->authorize('create', AuditReport::class);

        $report = $this->auditReportService->create(
            $request->validated(),
            $request->user()
        );

        $pdfContent = $this->auditReportService->generateAndSend($report);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $report->name . '.pdf"',
        ]);
    }

    public function show(AuditReport $auditReport): JsonResponse
    {
        $this->authorize('view', $auditReport);

        $auditReport->load('generatedBy');

        return $this->success($auditReport);
    }

    /**
     * @throws MpdfException
     */
    public function download(AuditReport $auditReport): Response
    {
        $this->authorize('view', $auditReport);

        $pdfContent = $this->auditReportService->generateReport($auditReport);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $auditReport->name . '.pdf"',
        ]);
    }
}

<?php

namespace App\Services\Audit;

use App\Models\AuditReport;
use App\Models\User;
use App\Notifications\AuditReportNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Notification;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class AuditReportService
{
    public function __construct(
        private AuditReportGeneratorService $generatorService
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(AuditReport::forUser($user))
            ->allowedFilters([
                AllowedFilter::exact('type'),
                AllowedFilter::exact('resource_id'),
                AllowedFilter::exact('generated_by'),
                AllowedFilter::callback('from', function ($query, $value) {
                    $query->where('generated_at', '>=', $value);
                }),
                AllowedFilter::callback('to', function ($query, $value) {
                    $query->where('generated_at', '<=', $value);
                }),
            ])
            ->allowedSorts(['generated_at', 'name'])
            ->allowedIncludes(['generatedBy'])
            ->defaultSort('-generated_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data, User $user): AuditReport
    {
        $data['company_id'] = $user->company_id;
        $data['generated_by'] = $user->id;
        $report = AuditReport::create($data);

        activity('audit-report')
            ->event('generated')
            ->performedOn($report)
            ->withProperties([
                'report_id' => $report->id,
                'type' => $report->type->value,
            ])
            ->log("Generated audit report \"$report->name\"");

        return $report;
    }

    /**
     * Generate audit report PDF
     */
    public function generateReport(AuditReport $report): string
    {
        return $this->generatorService->generate($report);
    }

    /**
     * Generate and send audit report via email
     */
    public function generateAndSend(AuditReport $report, array $recipientEmails = []): string
    {
        // Generate PDF
        $pdfContent = $this->generateReport($report);

        // Save to temporary storage
        $filename = "{$report->name}_{$report->id}.pdf";
        $tempPath = storage_path("app/temp/audit-reports/{$filename}");

        // Ensure directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        file_put_contents($tempPath, $pdfContent);

        // Get recipients - default to report generator if none provided
        $emails = !empty($recipientEmails) ? $recipientEmails : [$report->generatedBy->email];
        $recipients = User::whereIn('email', $emails)->get();

        if ($recipients->isNotEmpty()) {
            // Create notification
            $notification = new AuditReportNotification(
                auditReportId: $report->id,
                pdfPath: $tempPath
            );

            // Send notification
            Notification::send($recipients, $notification);
        }

        // Cleanup temp file after sending (optional - uncomment if needed)
        // if (file_exists($tempPath)) {
        //     @unlink($tempPath);
        // }

        return $pdfContent;
    }
}

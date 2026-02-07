<?php

namespace App\Services\Report;

use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportGeneratedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class ReportService
{
    public function __construct(
        private ReportGeneratorService $reportGenerator
    ) {}

    /**
     * Get paginated list of reports.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Report::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::callback("from", function ($query, $value) {
                    $query->where("generated_at", ">=", $value);
                }),
                AllowedFilter::callback("to", function ($query, $value) {
                    $query->where("generated_at", "<=", $value);
                }),
                AllowedFilter::exact("generated_by"),
                AllowedFilter::exact("device_id"),
                AllowedFilter::exact("company_id"),
            ])
            ->allowedSorts([
                "generated_at",
            ])
            ->allowedIncludes(["company", "device", "generatedBy"])
            ->defaultSort("-generated_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new report record
     */
    public function create(array $data, User $user): Report
    {
        // Create report record
        $data['generated_by'] = $user->id;
        $data['company_id'] = $user->company_id;
        $report = Report::create($data);

        // Audit log
        activity("report")
            ->event("generated")
            ->performedOn($report)
            ->withProperties([
                'report_id' => $report->id,
                'data_formation' => $report->data_formation->value,
            ])
            ->log("Generated report \"$report->name\"");

        return $report->fresh(['generatedBy']);
    }

    /**
     * Generate report file
     */
    public function generate(Report $report): string
    {
        return $this->reportGenerator->generateFromReportable($report);
    }

    /**
     * Generate report file and send notification
     */
    public function generateAndSend(Report $report): string
    {
        $pdfContent = $this->reportGenerator->generateFromReportable($report);

        // Save PDF to temp storage for notification attachment
        $filename = "{$report->name}_{$report->id}.pdf";
        $tempPath = storage_path("app/temp/reports/{$filename}");

        // Ensure directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        file_put_contents($tempPath, $pdfContent);

        // Send notification to report generator
        $report->generatedBy->notify(
            new ReportGeneratedNotification(
                reportId: $report->id,
                pdfPath: $tempPath,
            )
        );

        return $pdfContent;
    }
}

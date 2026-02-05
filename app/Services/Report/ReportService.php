<?php

namespace App\Services\Report;

use App\Models\Report;
use App\Models\User;
use Exception;
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
    public function create(array $data): Report
    {
        // Create report record
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

        return $report;
    }

    /**
     * Generate report file (PDF/CSV) from a Report model
     * @throws Exception
     */
    public function generateReport(Report $report): string
    {
        return $this->reportGenerator->generateFromReportable($report);
    }
}

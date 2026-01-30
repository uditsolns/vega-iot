<?php

namespace App\Services\Report;

use App\Models\Report;
use App\Models\User;
use App\Services\Audit\AuditService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class ReportService
{
    public function __construct(
        private AuditService $auditService,
        private ReportGeneratorService $reportGenerator
    ) {}

    /**
     * Get paginated list of reports.
     *
     * @param array $filters
     * @param User $user
     * @return LengthAwarePaginator
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
     * Create a new report.
     *
     * @param array $data
     * @return Report
     */
    public function create(array $data): Report
    {
        // Create report
        $report = Report::create($data);

        // Audit log
        $this->auditService->log("report.generated", Report::class, $report);

        return $report;
    }

    /**
     * Generate report file (PDF/CSV)
     *
     * @param Report $report
     * @return string File path
     * @throws Exception
     */
    public function generateReport(Report $report): string
    {
        return $this->reportGenerator->generate($report);
    }
}

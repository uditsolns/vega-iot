<?php

namespace App\Services\Audit;

use App\Models\AuditReport;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Mpdf\MpdfException;
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
     * @throws MpdfException
     */
    public function generateReport(AuditReport $report): string
    {
        return $this->generatorService->generate($report);
    }
}

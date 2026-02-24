<?php

namespace App\Services\Report;

use App\Enums\ReportFileType;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportGeneratedNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class ReportService
{
    public function __construct(
        private ReportGeneratorService $reportGenerator
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Report::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::callback('from', fn($q, $v) => $q->where('generated_at', '>=', $v)),
                AllowedFilter::callback('to', fn($q, $v) => $q->where('generated_at', '<=', $v)),
                AllowedFilter::exact('generated_by'),
                AllowedFilter::exact('device_id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedSorts(['generated_at'])
            ->allowedIncludes(['company', 'device', 'generatedBy'])
            ->defaultSort('-generated_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data, User $user): Report
    {
        $report = Report::create([
            'company_id'    => $user->company_id,
            'device_id'     => $data['device_id'],
            'generated_by'  => $user->id,
            'name'          => $data['name'],
            'file_type'     => $data['file_type'],
            'format'        => $data['format'],
            'sensor_ids'    => $data['sensor_ids'],
            'interval'      => $data['interval'],
            'from_datetime' => $data['from_datetime'],
            'to_datetime'   => $data['to_datetime'],
        ]);

        activity('report')
            ->event('generated')
            ->performedOn($report)
            ->withProperties(['sensor_ids' => $data['sensor_ids']])
            ->log("Generated report \"{$report->name}\"");

        return $report->fresh(['generatedBy']);
    }

    /**
     * Generate file content from a report record.
     * Returns raw bytes (PDF) or string (CSV).
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

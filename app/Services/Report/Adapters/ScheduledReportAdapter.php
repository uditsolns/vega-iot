<?php

namespace App\Services\Report\Adapters;

use App\Contracts\ReportableInterface;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Models\Device;
use App\Models\ScheduledReport;
use Carbon\Carbon;

/**
 * Adapter to make ScheduledReport compatible with ReportableInterface
 * This allows scheduled reports to be generated using the same logic as on-demand reports
 * without polluting the Report table with scheduled report records
 */
readonly class ScheduledReportAdapter implements ReportableInterface
{
    public function __construct(
        private ScheduledReport $scheduledReport,
        private Device          $device,
        private Carbon          $fromDatetime,
        private Carbon          $toDatetime,
    ) {}

    public function getReportName(): string
    {
        return "{$this->scheduledReport->name} - {$this->device->device_code}";
    }

    public function getFileType(): ReportFileType
    {
        return $this->scheduledReport->file_type;
    }

    public function getFormat(): ReportFormat
    {
        return $this->scheduledReport->format;
    }

    public function getDataFormation(): ReportDataFormation
    {
        return $this->scheduledReport->data_formation;
    }

    public function getInterval(): int
    {
        return $this->scheduledReport->interval;
    }

    public function getFromDatetime(): Carbon
    {
        return $this->fromDatetime;
    }

    public function getToDatetime(): Carbon
    {
        return $this->toDatetime;
    }

    public function getDeviceId(): int
    {
        return $this->device->id;
    }

    public function getCompanyId(): int
    {
        return $this->scheduledReport->company_id;
    }
}

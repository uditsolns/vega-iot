<?php

namespace App\Contracts;

use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use Carbon\Carbon;

/**
 * Interface for entities that can be used to generate reports
 */
interface ReportableInterface
{
    /**
     * Get the report name
     */
    public function getReportName(): string;

    /**
     * Get the file type for the report
     */
    public function getFileType(): ReportFileType;

    /**
     * Get the format for the report
     */
    public function getFormat(): ReportFormat;

    /**
     * Get the data formation type
     */
    public function getDataFormation(): ReportDataFormation;

    /**
     * Get the data interval in minutes
     */
    public function getInterval(): int;

    /**
     * Get the start datetime for the report
     */
    public function getFromDatetime(): Carbon;

    /**
     * Get the end datetime for the report
     */
    public function getToDatetime(): Carbon;

    /**
     * Get the device ID for which to generate the report
     */
    public function getDeviceId(): int;

    /**
     * Get the company ID for scoping
     */
    public function getCompanyId(): int;
}

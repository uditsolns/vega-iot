<?php

namespace App\DTOs;

use App\Contracts\ReportableInterface;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use Carbon\Carbon;

/**
 * Data Transfer Object for report generation
 */
readonly class ReportGenerationDTO
{
    public function __construct(
        public int                 $deviceId,
        public int                 $companyId,
        public string              $reportName,
        public ReportFileType      $fileType,
        public ReportFormat        $format,
        public ReportDataFormation $dataFormation,
        public int                 $interval,
        public Carbon              $fromDatetime,
        public Carbon              $toDatetime,
    ) {}

    /**
     * Create DTO from a Reportable entity
     */
    public static function fromReportable(ReportableInterface $reportable): self
    {
        return new self(
            deviceId: $reportable->getDeviceId(),
            companyId: $reportable->getCompanyId(),
            reportName: $reportable->getReportName(),
            fileType: $reportable->getFileType(),
            format: $reportable->getFormat(),
            dataFormation: $reportable->getDataFormation(),
            interval: $reportable->getInterval(),
            fromDatetime: $reportable->getFromDatetime(),
            toDatetime: $reportable->getToDatetime(),
        );
    }
}

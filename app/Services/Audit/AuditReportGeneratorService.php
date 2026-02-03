<?php

namespace App\Services\Audit;

use App\Enums\AuditReportType;
use App\Models\AuditReport;
use App\Models\Device;
use App\Models\User;
use App\Services\Audit\PDF\AuditPdfGeneratorService;
use Mpdf\MpdfException;
use Spatie\Activitylog\Models\Activity;

readonly class AuditReportGeneratorService
{
    public function __construct(
        private AuditPdfGeneratorService $pdfGenerator
    ) {}

    /**
     * @throws MpdfException
     */
    public function generate(AuditReport $report): string
    {
        $activities = $this->fetchActivities($report);
        $resourceData = $this->getResourceData($report);

        return $this->pdfGenerator->generate($report, $activities, $resourceData);
    }

    private function fetchActivities(AuditReport $report): array
    {
        $query = Activity::query()
            ->whereBetween('created_at', [$report->from_date, $report->to_date])
            ->with(['causer'])
            ->orderBy('created_at');

        if ($report->type === AuditReportType::User) {
            $query->where('causer_type', User::class)
                ->where('causer_id', $report->resource_id);
        } else {
            $query->where('subject_type', Device::class)
                ->where('subject_id', $report->resource_id);
        }

        return $query->get()->toArray();
    }

    private function getResourceData(AuditReport $report): array
    {
        if ($report->type === AuditReportType::User) {
            $user = User::with(['company', 'role'])->find($report->resource_id);
            return [
                'type' => 'user',
                'email' => $user->email,
                'name' => "{$user->first_name} {$user->last_name}",
                'role' => $user->role?->name ?? '-',
                'company' => $user->company?->name ?? '-',
            ];
        } else {
            $device = Device::with(['company', 'area.hub.location'])->find($report->resource_id);
            return [
                'type' => 'device',
                'device_code' => $device->device_code,
                'device_name' => $device->device_name ?? $device->device_code,
                'make' => $device->make,
                'model' => $device->model,
                'serial_no' => $device->device_uid,
                'temp_resolution' => $device->temp_resolution,
                'temp_accuracy' => $device->temp_accuracy,
                'company' => $device->company?->name ?? '-',
                'location' => $device->area?->hub?->location?->name ?? '-',
            ];
        }
    }
}

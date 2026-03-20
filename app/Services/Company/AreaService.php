<?php

namespace App\Services\Company;

use App\Models\Alert;
use App\Models\Area;
use App\Models\Hub;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

readonly class AreaService
{
    public function __construct(
        private FileUploadService $fileUploadService,
    ) {}

    /**
     * Get paginated list of areas.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(Area::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial("name"),
                AllowedFilter::partial("description"),
                AllowedFilter::exact("is_active"),
                AllowedFilter::exact("hub_id"),
            ])
            ->allowedSorts(["name", "created_at"])
            ->allowedIncludes(["hub", "devices"])
            ->defaultSort("-created_at")
            ->paginate($filters["per_page"] ?? 20);
    }

    /**
     * Create a new area.
     */
    public function create(array $data): Area
    {
        $hub  = Hub::with('location')->findOrFail($data['hub_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($hub->location->company_id != $user->company_id)) {
            throw new UnauthorizedException("Provided hub doesn't belong to you.");
        }

        return Area::create($data);
    }

    /**
     * Update an area.
     */
    public function update(Area $area, array $data): Area
    {
        $area->update($data);

        return $area->fresh();
    }

    /**
     * Delete an area (soft delete).
     */
    public function delete(Area $area): void
    {
        $area->delete();
    }

    /**
     * Activate an area.
     */
    public function activate(Area $area): Area
    {
        $area->update(["is_active" => true]);

        activity('area')
            ->performedOn($area)
            ->event('activated')
            ->withProperties(['area_id' => $area->id])
            ->log("Activated area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Deactivate an area.
     */
    public function deactivate(Area $area): Area
    {
        $area->update(["is_active" => false]);

        activity('area')
            ->performedOn($area)
            ->event('deactivated')
            ->withProperties(['area_id' => $area->id])
            ->log("Deactivated area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Restore a soft-deleted area.
     */
    public function restore(Area $area): Area
    {
        $area->restore();

        activity('area')
            ->performedOn($area)
            ->event('restored')
            ->withProperties(['area_id' => $area->id])
            ->log("Restored area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Update alert configuration for an area.
     */
    public function updateAlertConfig(Area $area, array $data): Area
    {
        $alertFields = [
            "alert_email_enabled",
            "alert_sms_enabled",
            "alert_voice_enabled",
            "alert_push_enabled",
            "alert_warning_enabled",
            "alert_critical_enabled",
            "alert_back_in_range_enabled",
            "alert_device_status_enabled",
            "acknowledged_alert_notification_interval",
        ];

        $area->update(array_intersect_key($data, array_flip($alertFields)));

        activity('area')
            ->event('alert_configuration_updated')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Updated alert configuration for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Copy alert configuration from source area to target areas.
     */
    public function copyAlertConfig(Area $sourceArea, array $targetAreaIds): array
    {
        $alertConfig = [
            "alert_email_enabled"                      => $sourceArea->alert_email_enabled,
            "alert_sms_enabled"                        => $sourceArea->alert_sms_enabled,
            "alert_voice_enabled"                      => $sourceArea->alert_voice_enabled,
            "alert_push_enabled"                       => $sourceArea->alert_push_enabled,
            "alert_warning_enabled"                    => $sourceArea->alert_warning_enabled,
            "alert_critical_enabled"                   => $sourceArea->alert_critical_enabled,
            "alert_back_in_range_enabled"              => $sourceArea->alert_back_in_range_enabled,
            "alert_device_status_enabled"              => $sourceArea->alert_device_status_enabled,
            "acknowledged_alert_notification_interval" => $sourceArea->acknowledged_alert_notification_interval,
        ];

        $updated = [];
        $failed  = [];

        foreach ($targetAreaIds as $areaId) {
            $targetArea = Area::find($areaId);

            if (!$targetArea) {
                $failed[] = ['id' => $areaId, 'reason' => "Area not found"];
                continue;
            }

            $targetArea->update($alertConfig);

            activity('area')
                ->event('alert_configuration_copied')
                ->performedOn($targetArea)
                ->withProperties([
                    'source_area_id' => $sourceArea->id,
                    'target_area_id' => $targetArea->id,
                ])
                ->log("Copied alert configuration from \"{$sourceArea->name}\" to \"{$targetArea->name}\"");

            $updated[] = $targetArea->id;
        }

        return ['updated' => $updated, 'failed' => $failed];
    }

    /**
     * Upload the mapping report for an area.
     */
    public function uploadMappingReport(Area $area, UploadedFile $file): Area
    {
        $path = $this->fileUploadService->store(
            $file,
            $this->reportDirectory($area),
            'mapping_report.pdf'
        );

        $area->update(['mapping_report_path' => $path]);

        activity('area')
            ->event('mapping_report_uploaded')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Uploaded mapping report for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Download the mapping report for an area.
     */
    public function downloadMappingReport(Area $area): StreamedResponse
    {
        return $this->fileUploadService->streamDownload(
            $area->mapping_report_path,
            "mapping_report_{$area->id}.pdf"
        );
    }

    /**
     * Delete the mapping report for an area.
     */
    public function deleteMappingReport(Area $area): Area
    {
        $this->fileUploadService->delete($area->mapping_report_path);

        $area->update(['mapping_report_path' => null]);

        activity('area')
            ->event('mapping_report_deleted')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Deleted mapping report for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Upload the device calibration report for an area.
     */
    public function uploadDeviceCalibrationReport(Area $area, UploadedFile $file): Area
    {
        $path = $this->fileUploadService->store(
            $file,
            $this->reportDirectory($area),
            'device_calibration_report.pdf'
        );

        $area->update(['device_calibration_report_path' => $path]);

        activity('area')
            ->event('device_calibration_report_uploaded')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Uploaded device calibration report for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Download the device calibration report for an area.
     */
    public function downloadDeviceCalibrationReport(Area $area): StreamedResponse
    {
        return $this->fileUploadService->streamDownload(
            $area->device_calibration_report_path,
            "device_calibration_report_{$area->id}.pdf"
        );
    }

    /**
     * Delete the device calibration report for an area.
     */
    public function deleteDeviceCalibrationReport(Area $area): Area
    {
        $this->fileUploadService->delete($area->device_calibration_report_path);

        $area->update(['device_calibration_report_path' => null]);

        activity('area')
            ->event('device_calibration_report_deleted')
            ->performedOn($area)
            ->withProperties(['area_id' => $area->id])
            ->log("Deleted device calibration report for area \"{$area->name}\"");

        return $area->fresh();
    }

    /**
     * Get devices for an area.
     */
    public function getDevices(Area $area, User $user): Collection
    {
        return $area->devices()
            ->forUser($user)
            ->with(["area", "currentConfiguration"])
            ->get();
    }

    /**
     * Get statistics for an area.
     */
    public function getStats(Area $area): array
    {
        // Get device IDs in this area
        $deviceIds = $area->devices()->pluck("id");

        return [
            "devices_count"  => $area->devices()->count(),
            "active_devices" => $area->devices()->where("is_active", true)->count(),
            "alerts_count"   => Alert::whereIn("device_id", $deviceIds)->count(),
            "active_alerts"  => Alert::whereIn("device_id", $deviceIds)->active()->count(),
            "open_alerts"    => Alert::whereIn("device_id", $deviceIds)->open()->count(),
        ];
    }

    // -------------------------------------------------------------------------

    private function reportDirectory(Area $area): string
    {
        $companyId = $area->hub->location->company_id ?? 'system';

        return "companies/{$companyId}/areas/{$area->id}";
    }
}

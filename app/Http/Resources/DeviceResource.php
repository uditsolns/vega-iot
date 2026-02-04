<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Device */
class DeviceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "device_uid" => $this->device_uid,
            "device_code" => $this->device_code,
            "make" => $this->make,
            "model" => $this->model,
            "type" => $this->type, // Enum automatically serializes to string
            "firmware_version" => $this->firmware_version,

            // Sensor specifications
            "temp_resolution" => $this->temp_resolution,
            "temp_accuracy" => $this->temp_accuracy,
            "humidity_resolution" => $this->humidity_resolution,
            "humidity_accuracy" => $this->humidity_accuracy,
            "temp_probe_resolution" => $this->temp_probe_resolution,
            "temp_probe_accuracy" => $this->temp_probe_accuracy,

            // Assignment
            "company_id" => $this->company_id,
            "area_id" => $this->area_id,
            "device_name" => $this->device_name,

            // Status
            "status" => $this->status, // Enum automatically serializes to string
            "is_active" => $this->is_active,
            "last_reading_at" => $this->last_reading_at,

            // Computed fields
            "deployment_status" => $this->getDeploymentStatus(),
            "location_path" => $this->getLocationPath(),

            // Relationships
            "company" => new CompanyResource($this->whenLoaded("company")),
            "area" => new AreaResource($this->whenLoaded("area")),
            "current_configuration" => new DeviceConfigurationResource(
                $this->whenLoaded("currentConfiguration"),
            ),
            "latest_reading" => new ReadingResource(
                $this->whenLoaded("latestReading"),
            ),

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }

    /**
     * Get deployment status
     */
    private function getDeploymentStatus(): string
    {
        if ($this->isSystemInventory()) {
            return "system_inventory";
        }

        if ($this->isCompanyInventory()) {
            return "company_inventory";
        }

        if ($this->isDeployed()) {
            return "deployed";
        }

        return "unknown";
    }
}

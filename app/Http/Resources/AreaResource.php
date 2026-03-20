<?php

namespace App\Http\Resources;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Area */
class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'hub_id'      => $this->hub_id,
            'name'        => $this->name,
            'description' => $this->description,
            'is_active'   => $this->is_active,

            // Alert channels
            'alert_email_enabled'         => $this->alert_email_enabled,
            'alert_sms_enabled'           => $this->alert_sms_enabled,
            'alert_voice_enabled'         => $this->alert_voice_enabled,
            'alert_push_enabled'          => $this->alert_push_enabled,

            // Alert types
            'alert_warning_enabled'            => $this->alert_warning_enabled,
            'alert_critical_enabled'           => $this->alert_critical_enabled,
            'alert_back_in_range_enabled'      => $this->alert_back_in_range_enabled,
            'alert_device_status_enabled'      => $this->alert_device_status_enabled,
            'acknowledged_alert_notification_interval' => $this->acknowledged_alert_notification_interval,

            // Reports — expose existence as boolean; never expose raw storage path
            'has_mapping_report'             => !is_null($this->mapping_report_path),
            'has_device_calibration_report'  => !is_null($this->device_calibration_report_path),

            // Relations
            'hub'     => new HubResource($this->whenLoaded('hub')),
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'devices_count' => $this->whenCounted('devices'),

            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

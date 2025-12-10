<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HierarchyTreeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Nested structure: location -> hubs -> areas
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,

            // Nested hubs with areas
            'hubs' => $this->whenLoaded('hubs', function () {
                return $this->hubs->map(function ($hub) {
                    return [
                        'id' => $hub->id,
                        'location_id' => $hub->location_id,
                        'name' => $hub->name,
                        'description' => $hub->description,
                        'is_active' => $hub->is_active,
                        'areas_count' => $hub->areas_count ?? $hub->areas->count(),

                        // Nested areas
                        'areas' => isset($hub->areas) ? $hub->areas->map(function ($area) {
                            return [
                                'id' => $area->id,
                                'hub_id' => $area->hub_id,
                                'name' => $area->name,
                                'description' => $area->description,
                                'is_active' => $area->is_active,

                                // Alert configuration
                                'alert_email_enabled' => $area->alert_email_enabled,
                                'alert_sms_enabled' => $area->alert_sms_enabled,
                                'alert_voice_enabled' => $area->alert_voice_enabled,
                                'alert_push_enabled' => $area->alert_push_enabled,
                                'alert_warning_enabled' => $area->alert_warning_enabled,
                                'alert_critical_enabled' => $area->alert_critical_enabled,
                                'alert_back_in_range_enabled' => $area->alert_back_in_range_enabled,
                                'alert_device_status_enabled' => $area->alert_device_status_enabled,
                                'acknowledged_alert_notification_interval' => $area->acknowledged_alert_notification_interval,

                                'created_at' => $area->created_at,
                                'updated_at' => $area->updated_at,
                            ];
                        }) : [],

                        'created_at' => $hub->created_at,
                        'updated_at' => $hub->updated_at,
                    ];
                });
            }),

            // Counts at location level
            'hubs_count' => $this->whenCounted('hubs'),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Alert
 */
class AlertResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'severity' => $this->severity->value,
            'severity_label' => $this->severity->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            // Alert details
            'trigger_value' => $this->trigger_value,
            'threshold_breached' => $this->threshold_breached,
            'reason' => $this->reason,

            // Timestamps
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_formatted,

            // Acknowledgment
            'acknowledged_at' => $this->acknowledged_at,
            'acknowledged_by' => $this->acknowledged_by,
            'acknowledge_comment' => $this->acknowledge_comment,

            // Resolution
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->resolved_by,
            'resolve_comment' => $this->resolve_comment,
            'is_back_in_range' => $this->is_back_in_range,

            // Notification tracking
            'last_notification_at' => $this->last_notification_at,
            'notification_count' => $this->notification_count,

            'created_at' => $this->created_at,

            // Computed attributes
            'device_name' => $this->device_name,
            'area_name' => $this->area_name,

            // Relationships
            'device' => new DeviceResource($this->whenLoaded('device')),
            'acknowledged_by_user' => new UserResource($this->whenLoaded('acknowledgedBy')),
            'resolved_by_user' => new UserResource($this->whenLoaded('resolvedBy')),
            'notifications' => AlertNotificationResource::collection($this->whenLoaded('notifications')),
            'notifications_count' => $this->whenCounted('notifications'),
        ];
    }
}

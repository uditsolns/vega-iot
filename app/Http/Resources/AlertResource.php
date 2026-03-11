<?php

namespace App\Http\Resources;

use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Alert */
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

            // Resolution
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->resolved_by,

            'possible_cause' => $this->possible_cause,
            'root_cause' => $this->root_cause,
            'corrective_action' => $this->corrective_action,

            // Notification tracking
            'last_notification_at' => $this->last_notification_at,
            'notification_count' => $this->notification_count,

            'created_at' => $this->created_at,

            // Relationships
            'device' => new DeviceResource($this->whenLoaded('device')),
            'acknowledged_by_user' => new UserResource($this->whenLoaded('acknowledgedBy')),
            'resolved_by_user' => new UserResource($this->whenLoaded('resolvedBy')),
        ];
    }
}

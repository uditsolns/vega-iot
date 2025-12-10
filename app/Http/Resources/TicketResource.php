<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "subject" => $this->subject,
            "description" => $this->description,
            "reason" => $this->reason,
            "status" => $this->status->value,
            "status_label" => $this->status_label,
            "priority" => $this->priority->value,
            "priority_label" => $this->priority_label,
            "user_id" => $this->user_id,
            "assigned_to" => $this->assigned_to,
            "device_id" => $this->device_id,
            "location_id" => $this->location_id,
            "hub_id" => $this->hub_id,
            "area_id" => $this->area_id,
            "created_at" => $this->created_at->toISOString(),
            "updated_at" => $this->updated_at->toISOString(),
            "resolved_at" => $this->resolved_at?->toISOString(),
            "closed_at" => $this->closed_at?->toISOString(),
            "is_open" => $this->is_open,
            "is_closed" => $this->is_closed,

            // Conditional includes
            "user" => new UserResource($this->whenLoaded("user")),
            "assigned_to_user" => new UserResource(
                $this->whenLoaded("assignedTo"),
            ),
            "device" => new DeviceResource($this->whenLoaded("device")),
            "location" => new LocationResource($this->whenLoaded("location")),
            "area" => new AreaResource($this->whenLoaded("area")),
            "company" => new CompanyResource($this->whenLoaded("company")),
            "comments" => TicketCommentResource::collection(
                $this->whenLoaded("comments"),
            ),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertNotificationResource extends JsonResource
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
            'alert_id' => $this->alert_id,
            'user_id' => $this->user_id,
            'channel' => $this->channel,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'failed_at' => $this->failed_at?->toISOString(),
            'error_message' => $this->error_message,
            'notification_reference' => $this->notification_reference,
            'created_at' => $this->created_at->toISOString(),

            // Relationships
            'alert' => new AlertResource($this->whenLoaded('alert')),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

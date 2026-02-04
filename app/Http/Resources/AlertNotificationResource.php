<?php

namespace App\Http\Resources;

use App\Models\AlertNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AlertNotification */
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
            "id" => $this->id,
            "alert_id" => $this->alert_id,
            "user_id" => $this->user_id,
            "channel" => $this->channel,
            "status" => $this->status->value,
            "event" => $this->event,
            "queued_at" => $this->queued_at?->toISOString(),
            "sent_at" => $this->sent_at?->toISOString(),
            "failed_at" => $this->failed_at?->toISOString(),
            "retry_count" => $this->retry_count,
            "error_message" => $this->error_message,
            "message_content" => $this->message_content,
            "external_reference" => $this->external_reference,

            // Relationships
            "alert" => new AlertResource($this->whenLoaded("alert")),
            "user" => new UserResource($this->whenLoaded("user")),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketComment */
class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "ticket_id" => $this->ticket_id,
            "user_id" => $this->user_id,
            "comment" => $this->comment,
            "is_internal" => $this->is_internal,
            "created_at" => $this->created_at->toISOString(),

            // Conditional
            "user" => new UserResource($this->whenLoaded("user")),
        ];
    }
}

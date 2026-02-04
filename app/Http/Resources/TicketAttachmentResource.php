<?php

namespace App\Http\Resources;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketAttachment */
class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "ticket_id" => $this->ticket_id,
            "comment_id" => $this->comment_id,
            "file_name" => $this->file_name,
            "file_type" => $this->file_type,
            "file_size" => $this->file_size,
            "file_size_formatted" => $this->file_size_formatted,
            "download_url" => route("api.v1.tickets.attachments.download", [
                "ticket" => $this->ticket_id,
                "attachment" => $this->id,
            ]),
            "uploaded_by" => $this->uploaded_by,
            "uploaded_at" => $this->uploaded_at->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\ScheduledReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduledReport */
class ScheduledReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'frequency' => $this->frequency,
            'timezone' => $this->timezone,
            'time' => $this->time,
            'recipient_emails' => $this->recipient_emails,
            'device_type' => $this->device_type,
            'file_type' => $this->file_type,
            'format' => $this->format,
            'data_formation' => $this->data_formation,
            'interval' => $this->interval,
            'is_active' => $this->is_active,
            'last_run_at' => $this->last_run_at,
            'next_run_at' => $this->next_run_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'company_id' => $this->company_id,
            'created_by' => $this->created_by,

            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
        ];
    }
}

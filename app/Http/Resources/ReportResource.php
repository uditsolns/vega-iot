<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'file_type' => $this->file_type,
            'format' => $this->format,
            'data_formation' => $this->data_formation,
            'interval' => $this->interval,
            'from_datetime' => $this->from_datetime,
            'to_datetime' => $this->to_datetime,
            'generated_at' => $this->generated_at,

            'company_id' => $this->company_id,
            'device_id' => $this->device_id,
            'generated_by' => $this->generated_by,

            'company' => new CompanyResource($this->whenLoaded('company')),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'generated_by_user' => new UserResource($this->whenLoaded('generatedBy')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\ValidationStudy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ValidationStudy */
class ValidationStudyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'area_type' => $this->area_type,
            'area_reference' => $this->area_reference,
            'number_of_loggers' => $this->number_of_loggers,
            'cfa' => $this->cfa,
            'location' => $this->location,
            'qualification_type' => $this->qualification_type,
            'reason' => $this->reason,
            'temperature_range' => $this->temperature_range,
            'duration' => $this->duration,
            'mapping_start_at' => $this->mapping_start_at,
            'mapping_end_at' => $this->mapping_end_at,
            'mapping_due_at' => $this->mapping_due_at,
            'report_path' => $this->report_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'company_id' => $this->company_id,

            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}

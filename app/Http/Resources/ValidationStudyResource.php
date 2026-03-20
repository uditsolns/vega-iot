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
            'id'         => $this->id,
            'company_id' => $this->company_id,

            // Area
            'area_type'      => $this->area_type,
            'area_reference' => $this->area_reference,

            // Study details
            'number_of_loggers' => $this->number_of_loggers,
            'cfa'               => $this->cfa,
            'location'          => $this->location,

            // Qualification
            'qualification_type'       => $this->qualification_type?->value,
            'qualification_type_label' => $this->qualification_type?->label(),
            'reason'                   => $this->reason,

            // Conditions
            'temperature_range' => $this->temperature_range,
            'duration'          => $this->duration,

            // Schedule
            'mapping_start_at' => $this->mapping_start_at?->toDateString(),
            'mapping_end_at'   => $this->mapping_end_at?->toDateString(),
            'mapping_due_at'   => $this->mapping_due_at?->toDateString(),

            // Report — expose existence only; never expose raw storage path
            'has_report' => !is_null($this->report_path),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

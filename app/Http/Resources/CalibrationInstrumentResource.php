<?php

namespace App\Http\Resources;

use App\Models\CalibrationInstrument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CalibrationInstrument */
class CalibrationInstrumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instrument_name' => $this->instrument_name,
            'instrument_code' => $this->instrument_code,
            'serial_no' => $this->serial_no,
            'make' => $this->make,
            'model' => $this->model,
            'location' => $this->location,
            'measurement_range' => $this->measurement_range,
            'resolution' => $this->resolution,
            'accuracy' => $this->accuracy,
            'last_calibrated_at' => $this->last_calibrated_at,
            'calibration_due_at' => $this->calibration_due_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'company_id' => $this->company_id,

            'company' => new CompanyResource($this->whenLoaded('company')),
        ];
    }
}

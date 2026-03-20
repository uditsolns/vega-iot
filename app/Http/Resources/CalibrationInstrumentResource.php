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
            'id'             => $this->id,
            'company_id'     => $this->company_id,
            'company_name'   => $this->company_name,

            // Identity
            'instrument_name' => $this->instrument_name,
            'instrument_code' => $this->instrument_code,
            'serial_no'       => $this->serial_no,

            // Manufacturer
            'make'  => $this->make,
            'model' => $this->model,

            // Location
            'location' => $this->location,

            // Technical specs
            'measurement_range' => $this->measurement_range,
            'resolution'        => $this->resolution,
            'accuracy'          => $this->accuracy,

            // Calibration tracking
            'last_calibrated_at' => $this->last_calibrated_at?->toDateString(),
            'calibration_due_at' => $this->calibration_due_at?->toDateString(),

            // Report — expose existence only; never expose raw storage path
            'has_report' => !is_null($this->report_path),

            'is_active'  => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

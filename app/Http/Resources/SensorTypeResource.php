<?php

namespace App\Http\Resources;


use App\Models\SensorType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SensorType */
class SensorTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'data_type' => $this->data_type,
            'unit' => $this->unit,
            'supports_threshold_config' => $this->supports_threshold_config,
        ];
    }
}

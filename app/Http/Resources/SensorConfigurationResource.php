<?php

namespace App\Http\Resources;

use App\Models\SensorConfiguration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SensorConfiguration */
class SensorConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_critical' => $this->min_critical,
            'max_critical' => $this->max_critical,
            'min_warning' => $this->min_warning,
            'max_warning' => $this->max_warning,
            'effective_from' => $this->effective_from->toISOString(),
            'effective_to' => $this->effective_to?->toISOString(),
            'is_current' => is_null($this->effective_to),
            'updated_by' => $this->whenLoaded('updatedBy', fn() => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => "{$this->updatedBy->first_name} {$this->updatedBy->last_name}",
            ] : null),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

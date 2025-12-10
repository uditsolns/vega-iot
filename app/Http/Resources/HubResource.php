<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,

            // Relationships
            'location' => new LocationResource($this->whenLoaded('location')),
            'areas' => AreaResource::collection($this->whenLoaded('areas')),

            // Counts
            'areas_count' => $this->whenCounted('areas'),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at !== null, $this->deleted_at),
        ];
    }
}

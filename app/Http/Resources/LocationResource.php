<?php

namespace App\Http\Resources;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Location */
class LocationResource extends JsonResource
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
            'company_id' => $this->company_id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,

            // Relationships
            'company' => new CompanyResource($this->whenLoaded('company')),
            'hubs' => HubResource::collection($this->whenLoaded('hubs')),

            // Counts
            'hubs_count' => $this->whenCounted('hubs'),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->when($this->deleted_at !== null, $this->deleted_at),
        ];
    }
}

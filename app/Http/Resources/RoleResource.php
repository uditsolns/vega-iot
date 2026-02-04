<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "hierarchy_level" => $this->hierarchy_level,
            "is_system_role" => $this->is_system_role,
            "is_editable" => $this->is_editable,

            // Conditional: Load permissions when relationship is loaded
            "permissions" => PermissionResource::collection(
                $this->whenLoaded("permissions"),
            ),

            // Conditional: Show users count when available
            "users_count" => $this->whenCounted("users"),

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}

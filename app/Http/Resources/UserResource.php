<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin User */
class UserResource extends JsonResource
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
            "role_id" => $this->role_id,
            "company_id" => $this->company_id,
            "email" => $this->email,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "phone" => $this->phone,
            "is_active" => $this->is_active,
            "last_login_at" => $this->last_login_at,
            "dark_theme_enabled" => $this->dark_theme_enabled,
            "work_mode" => $this->work_mode,

            "company" => new CompanyResource($this->whenLoaded("company")),
            "role" => new RoleResource($this->whenLoaded("role")),
            "permissions" => PermissionResource::collection(
                $this->whenLoaded("permissions"),
            ),

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}

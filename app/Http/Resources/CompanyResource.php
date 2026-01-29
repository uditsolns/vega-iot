<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Company */
class CompanyResource extends JsonResource
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
            "client_name" => $this->client_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "billing_address" => $this->billing_address,
            "shipping_address" => $this->shipping_address,
            "gst_number" => $this->gst_number,
            "is_active" => $this->is_active,
            "is_hierarchy_enabled" => $this->is_hierarchy_enabled,
            "is_csv_export_enabled" => $this->is_csv_export_enabled,
            "is_device_config_enabled" => $this->is_device_config_enabled,

            // Conditional: Show users count when available
            "users" => $this->whenLoaded("users"),
            "users_count" => $this->whenCounted("users"),
            "users_exists" => $this->whenExistsLoaded("users"),
            "devices_count" => $this->whenCounted("devices"),

            "roles" => $this->whenLoaded("roles"),
            "roles_exists" => $this->whenExistsLoaded("roles"),
            "roles_count" => $this->whenCounted("roles"),

            // Timestamps
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
        ];
    }
}

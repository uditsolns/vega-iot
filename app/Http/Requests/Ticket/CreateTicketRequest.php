<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            "subject" => ["required", "string", "max:255"],
            "description" => ["required", "string"],
            "reason" => ["nullable", "string", "max:100"],
            "priority" => ["required", Rule::enum(TicketPriority::class)],
            "device_id" => ["nullable", "integer", "exists:devices,id"],
            "location_id" => ["nullable", "integer", "exists:locations,id"],
            "area_id" => ["nullable", "integer", "exists:areas,id"],
        ];
    }

    public function messages(): array
    {
        return [
            "subject.required" => "The subject field is required.",
            "subject.max" =>
                "The subject may not be greater than 255 characters.",
            "description.required" => "The description field is required.",
            "reason.max" =>
                "The reason may not be greater than 100 characters.",
            "priority.required" => "The priority field is required.",
            "device_id.exists" => "The selected device does not exist.",
            "location_id.exists" => "The selected location does not exist.",
            "area_id.exists" => "The selected area does not exist.",
        ];
    }
}

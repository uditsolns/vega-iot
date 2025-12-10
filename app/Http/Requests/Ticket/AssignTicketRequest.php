<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            "assigned_to" => ["required", "integer", "exists:users,id"],
        ];
    }

    public function messages(): array
    {
        return [
            "assigned_to.required" => "The assigned to field is required.",
            "assigned_to.exists" => "The selected user does not exist.",
        ];
    }
}

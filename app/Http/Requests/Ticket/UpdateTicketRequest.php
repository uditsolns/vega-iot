<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            "subject" => ["string", "max:255"],
            "description" => ["string"],
            "reason" => ["nullable", "string", "max:100"],
            "priority" => ["sometimes", Rule::enum(TicketPriority::class)],
        ];
    }

    public function messages(): array
    {
        return [
            "subject.max" =>
                "The subject may not be greater than 255 characters.",
            "reason.max" =>
                "The reason may not be greater than 100 characters.",
        ];
    }
}

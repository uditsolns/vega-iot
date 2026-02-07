<?php

namespace App\Http\Requests\Ticket;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /**
     * Configure the validator instance.
     * Ensure the assigned user is VEGA's internal support (company_id is null)
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->input('assigned_to');
            if ($userId) {
                $user = User::find($userId);

                // Ensure user is VEGA's internal support (not a customer user)
                if ($user && $user->company_id !== null) {
                    $validator->errors()->add(
                        'assigned_to',
                        'Only VEGA internal support users can be assigned to tickets. Customer users cannot be assigned.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            "assigned_to.required" => "The assigned to field is required.",
            "assigned_to.exists" => "The selected user does not exist.",
        ];
    }
}

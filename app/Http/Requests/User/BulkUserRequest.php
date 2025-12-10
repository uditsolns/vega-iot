<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer', 'exists:users,id'],

            // For bulk role change
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],

            // For bulk area grant/revoke
            'area_ids' => ['sometimes', 'array', 'min:1'],
            'area_ids.*' => ['required', 'integer', 'exists:areas,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'At least one user is required',
            'user_ids.array' => 'User IDs must be an array',
            'user_ids.min' => 'At least one user is required',
            'user_ids.*.exists' => 'One or more selected users do not exist',
            'role_id.required' => 'Role is required',
            'role_id.exists' => 'Selected role does not exist',
            'area_ids.array' => 'Area IDs must be an array',
            'area_ids.min' => 'At least one area is required',
            'area_ids.*.exists' => 'One or more selected areas do not exist',
        ];
    }
}

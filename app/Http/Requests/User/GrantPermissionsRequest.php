<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GrantPermissionsRequest extends FormRequest
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
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'integer', 'exists:permissions,id'],
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
            'permission_ids.required' => 'At least one permission is required',
            'permission_ids.array' => 'Permission IDs must be an array',
            'permission_ids.min' => 'At least one permission is required',
            'permission_ids.*.exists' => 'One or more selected permissions do not exist',
        ];
    }
}

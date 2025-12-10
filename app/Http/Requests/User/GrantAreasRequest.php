<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class GrantAreasRequest extends FormRequest
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
            'area_ids' => ['required', 'array', 'min:1'],
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
            'area_ids.required' => 'At least one area is required',
            'area_ids.array' => 'Area IDs must be an array',
            'area_ids.min' => 'At least one area is required',
            'area_ids.*.exists' => 'One or more selected areas do not exist',
        ];
    }
}

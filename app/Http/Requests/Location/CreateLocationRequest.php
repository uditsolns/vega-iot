<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLocationRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations')->where(function ($query) {
                    return $query->where('company_id', $this->company_id);
                }),
            ],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => [
                'required',
                'string',
                'in:Asia/Kolkata,America/New_York,America/Los_Angeles,America/Chicago,Europe/London,Europe/Paris,Asia/Dubai,Asia/Singapore,Asia/Tokyo,Australia/Sydney,Pacific/Auckland',
            ],
            'is_active' => ['sometimes', 'boolean'],
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
            'company_id.required' => 'Company is required',
            'company_id.exists' => 'The selected company does not exist',
            'name.required' => 'Location name is required',
            'name.max' => 'Location name must not exceed 255 characters',
            'name.unique' => 'A location with this name already exists for this company',
            'timezone.required' => 'Timezone is required',
            'timezone.in' => 'The selected timezone is not valid',
        ];
    }
}

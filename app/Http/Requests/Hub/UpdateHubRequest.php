<?php

namespace App\Http\Requests\Hub;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHubRequest extends FormRequest
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
        $hub = $this->route('hub');

        return [
            'location_id' => ['sometimes', 'required', 'integer', 'exists:locations,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('hubs')
                    ->ignore($hub)
                    ->where(function ($query) {
                        $locationId = $this->location_id ?? $this->route('hub')->location_id;
                        return $query->where('location_id', $locationId);
                    }),
            ],
            'description' => ['nullable', 'string'],
//            'is_active' => ['sometimes', 'boolean'], // an explicit endpoint available
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
            'location_id.required' => 'Location is required',
            'location_id.exists' => 'The selected location does not exist',
            'name.required' => 'Hub name is required',
            'name.max' => 'Hub name must not exceed 255 characters',
            'name.unique' => 'A hub with this name already exists for this location',
        ];
    }
}

<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceToAreaRequest extends FormRequest
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
            "area_id" => ["required", "integer", "exists:areas,id"],
            "device_name" => ["nullable", "string", "max:255"],
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
            "area_id.required" => "The area field is required.",
            "area_id.exists" => "The selected area does not exist.",
        ];
    }
}

<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignDevicesToCompanyRequest extends FormRequest
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
            "device_ids" => ["required", "array", "min:1"],
            "device_ids.*" => ["required", "integer", "exists:devices,id"],
            "company_id" => ["required", "integer", "exists:companies,id"],
        ];
    }
}

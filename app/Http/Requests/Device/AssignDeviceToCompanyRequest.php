<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceToCompanyRequest extends FormRequest
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
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests\Reading;

use Illuminate\Foundation\Http\FormRequest;

class ListReadingsRequest extends FormRequest
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
            // Pagination
            "per_page" => ["sometimes", "integer", "min:1", "max:200"],

            // Time filters
            "from" => ["sometimes", "date"],
            "to" => ["sometimes", "date", "after_or_equal:from"],
            "date" => ["sometimes", "date"],

            // Hierarchy filters
            "filter.device_id" => ["sometimes", "integer", "exists:devices,id"],
            "filter.company_id" => [
                "sometimes",
                "integer",
                "exists:companies,id",
            ],
            "filter.location_id" => [
                "sometimes",
                "integer",
                "exists:locations,id",
            ],
            "filter.hub_id" => ["sometimes", "integer", "exists:hubs,id"],
            "filter.area_id" => ["sometimes", "integer", "exists:areas,id"],
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
            "to.after_or_equal" => "The 'to' date must be after or equal to the 'from' date.",
            "per_page.max" => "Maximum items per page is 200.",
        ];
    }
}

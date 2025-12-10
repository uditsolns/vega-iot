<?php

namespace App\Http\Requests\Reading;

use Illuminate\Foundation\Http\FormRequest;

class GetAggregationsRequest extends FormRequest
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
            // Time range (required for aggregations)
            "from" => ["required", "date"],
            "to" => ["required", "date", "after_or_equal:from"],

            // Aggregation interval
            "interval" => [
                "sometimes",
                "string",
                "in:1 minute,5 minutes,10 minutes,15 minutes,30 minutes,1 hour,2 hours,6 hours,12 hours,1 day,7 days",
            ],

            // Filters
            "device_id" => ["sometimes", "integer", "exists:devices,id"],
            "area_id" => ["sometimes", "integer", "exists:areas,id"],
            "hub_id" => ["sometimes", "integer", "exists:hubs,id"],
            "location_id" => ["sometimes", "integer", "exists:locations,id"],
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
            "from.required" => "The 'from' date is required for aggregations.",
            "to.required" => "The 'to' date is required for aggregations.",
            "to.after_or_equal" => "The 'to' date must be after or equal to the 'from' date.",
            "interval.in" => "Invalid aggregation interval. Valid values: 1 minute, 5 minutes, 10 minutes, 15 minutes, 30 minutes, 1 hour, 2 hours, 6 hours, 12 hours, 1 day, 7 days.",
        ];
    }
}

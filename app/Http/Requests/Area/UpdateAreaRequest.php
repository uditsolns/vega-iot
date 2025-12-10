<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
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
        $area = $this->route('area');

        return [
            'hub_id' => ['sometimes', 'required', 'integer', 'exists:hubs,id'],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('areas')
                    ->ignore($area)
                    ->where(function ($query) {
                        $hubId = $this->hub_id ?? $this->route('area')->hub_id;
                        return $query->where('hub_id', $hubId);
                    }),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'alert_email_enabled' => ['sometimes', 'boolean'],
            'alert_sms_enabled' => ['sometimes', 'boolean'],
            'alert_voice_enabled' => ['sometimes', 'boolean'],
            'alert_push_enabled' => ['sometimes', 'boolean'],
            'alert_warning_enabled' => ['sometimes', 'boolean'],
            'alert_critical_enabled' => ['sometimes', 'boolean'],
            'alert_back_in_range_enabled' => ['sometimes', 'boolean'],
            'alert_device_status_enabled' => ['sometimes', 'boolean'],
            'acknowledged_alert_notification_interval' => ['sometimes', 'integer', 'min:1'],
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
            'hub_id.required' => 'Hub is required',
            'hub_id.exists' => 'The selected hub does not exist',
            'name.required' => 'Area name is required',
            'name.max' => 'Area name must not exceed 255 characters',
            'name.unique' => 'An area with this name already exists for this hub',
            'acknowledged_alert_notification_interval.min' => 'Notification interval must be at least 1 hour',
        ];
    }
}

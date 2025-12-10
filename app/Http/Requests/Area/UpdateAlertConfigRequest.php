<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertConfigRequest extends FormRequest
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
            'acknowledged_alert_notification_interval.min' => 'Notification interval must be at least 1 hour',
        ];
    }
}

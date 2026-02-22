<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSensorConfigurationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'min_critical' => ['nullable', 'numeric'],
            'max_critical' => ['nullable', 'numeric'],
            'min_warning' => ['nullable', 'numeric'],
            'max_warning' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $d = $this->all();

            if (isset($d['min_critical'], $d['max_critical']) && $d['min_critical'] >= $d['max_critical']) {
                $validator->errors()->add('max_critical', 'max_critical must be greater than min_critical.');
            }

            if (isset($d['min_warning'], $d['max_warning']) && $d['min_warning'] >= $d['max_warning']) {
                $validator->errors()->add('max_warning', 'max_warning must be greater than min_warning.');
            }

            if (isset($d['min_warning'], $d['min_critical']) && $d['min_warning'] < $d['min_critical']) {
                $validator->errors()->add('min_warning', 'min_warning must be >= min_critical.');
            }

            if (isset($d['max_warning'], $d['max_critical']) && $d['max_warning'] > $d['max_critical']) {
                $validator->errors()->add('max_warning', 'max_warning must be <= max_critical.');
            }
        });
    }
}

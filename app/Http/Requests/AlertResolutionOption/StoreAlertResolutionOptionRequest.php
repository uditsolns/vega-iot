<?php

namespace App\Http\Requests\AlertResolutionOption;

use App\Enums\AlertResolutionOptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAlertResolutionOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'type'       => ['required', new Enum(AlertResolutionOptionType::class)],
            'label'      => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

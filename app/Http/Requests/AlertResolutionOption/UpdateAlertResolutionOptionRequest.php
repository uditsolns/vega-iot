<?php

namespace App\Http\Requests\AlertResolutionOption;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertResolutionOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'label'      => ['sometimes', 'string', 'max:100'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}

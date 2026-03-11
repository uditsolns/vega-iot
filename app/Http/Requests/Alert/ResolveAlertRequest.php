<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;

class ResolveAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'possible_cause'    => ['required', 'string', 'max:200'],
            'root_cause'        => ['required', 'string', 'max:200'],
            'corrective_action' => ['required', 'string', 'max:200'],
        ];
    }
}

<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
            'is_internal' => ['boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'The comment field is required.',
            'is_internal.boolean' => 'The is internal field must be true or false.',
            'attachments.array' => 'Attachments must be an array.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment may not be greater than 10MB.',
            'attachments.*.mimes' => 'Attachments must be of type: jpg, jpeg, png, pdf, doc, docx, txt.',
        ];
    }
}

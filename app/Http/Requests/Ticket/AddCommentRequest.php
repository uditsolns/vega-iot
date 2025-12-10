<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt,zip'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'The comment field is required.',
            'attachments.max' => 'You may not upload more than 5 files at once.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment may not be greater than 10MB.',
            'attachments.*.mimes' => 'Attachments must be of type: jpg, jpeg, png, pdf, doc, docx, txt, zip.',
        ];
    }
}

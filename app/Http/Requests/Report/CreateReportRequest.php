<?php

namespace App\Http\Requests\Report;

use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'device_id' => ['required', 'exists:devices,id'],
            'name' => ['required', 'string'],
            'file_type' => ['required', Rule::enum(ReportFileType::class)],
            'report_format' => ['required', Rule::enum(ReportFormat::class)],
            'data_formation' => ['required', Rule::enum(ReportDataFormation::class)],
            'interval' => ['required', 'integer', "between:5,60"],
            'from_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'to_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}

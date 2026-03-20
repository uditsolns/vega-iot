<?php

namespace App\Imports;

use App\Enums\ValidationQualificationType;
use App\Models\ValidationStudy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ValidationStudyImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $companyId;
    private array $errors = [];
    private int $importedCount = 0;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public function collection(Collection $rows): void
    {
//        $validQualificationTypes = ValidationQualificationType::values();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data      = $this->castRow($row->toArray());
            \Log::debug("Validation Study Data: ", $data);

            $validator = Validator::make($data, [
                'area_type'          => ['nullable', 'string', 'max:255'],
                'area_reference'     => ['nullable', 'string', 'max:255'],
                'number_of_loggers'  => ['nullable', 'integer', 'min:1'],
                'cfa'                => ['nullable', 'string', 'max:50'],
                'location'           => ['nullable', 'string', 'max:255'],
                'qualification_type' => ['nullable', Rule::enum(ValidationQualificationType::class)],
                'reason'             => ['nullable', 'string', 'max:255'],
                'temperature_range'  => ['nullable', 'string', 'max:255'],
                'duration'           => ['nullable', 'string', 'max:255'],
                'mapping_start_at'   => ['nullable', 'date'],
                'mapping_end_at'     => ['nullable', 'date'],
                'mapping_due_at'     => ['nullable', 'date'],
            ], [
                'qualification_type.enum' => 'Invalid qualification_type. Accepted values: ' . implode(', ', ValidationQualificationType::values()) . '.',
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row'    => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            ValidationStudy::create([
                'company_id'         => $this->companyId,
                'area_type'          => $data['area_type'] ?? null,
                'area_reference'     => $data['area_reference'] ?? null,
                'number_of_loggers'  => isset($data['number_of_loggers']) ? (int) $data['number_of_loggers'] : null,
                'cfa'                => $data['cfa'] ?? null,
                'location'           => $data['location'] ?? null,
                'qualification_type' => $data['qualification_type'] ?? null,
                'reason'             => $data['reason'] ?? null,
                'temperature_range'  => $data['temperature_range'] ?? null,
                'duration'           => $data['duration'] ?? null,
                'mapping_start_at'   => $data['mapping_start_at'] ?? null,
                'mapping_end_at'     => $data['mapping_end_at'] ?? null,
                'mapping_due_at'     => $data['mapping_due_at'] ?? null,
            ]);

            $this->importedCount++;
        }
    }

    /**
     * Normalise a raw Excel row before validation.
     */
    private function castRow(array $data): array
    {
        // Normalise qualification_type to uppercase so "iq", "Iq", "IQ" all pass
        if (!empty($data['qualification_type'])) {
            $data['qualification_type'] = strtoupper(trim($data['qualification_type']));
        }

        // Fields that must be strings regardless of what Excel infers
        $stringFields = ['area_reference', 'duration'];
        foreach ($stringFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = (string) $data[$field];
            }
        }

        $dateFields = ['mapping_start_at', 'mapping_end_at', 'mapping_due_at'];
        foreach ($dateFields as $field) {
            $data[$field] = $this->normaliseDate($data[$field] ?? null);
        }

        return $data;
    }

    /**
     * Convert an Excel cell value to a Y-m-d string, or return null.
     */
    private function normaliseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return (string) $value;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public static function expectedHeadings(): array
    {
        return [
            'area_type',
            'area_reference',
            'number_of_loggers',
            'cfa',
            'location',
            'qualification_type',
            'reason',
            'temperature_range',
            'duration',
            'mapping_start_at',
            'mapping_end_at',
            'mapping_due_at',
        ];
    }
}

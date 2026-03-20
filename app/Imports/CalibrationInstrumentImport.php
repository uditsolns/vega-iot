<?php

namespace App\Imports;

use App\Models\CalibrationInstrument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CalibrationInstrumentImport implements ToCollection, WithHeadingRow, WithChunkReading
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
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2: heading row + 0-based index
            $data      = $this->castRow($row->toArray());

            $validator = Validator::make($data, [
                'company_name'       => ['nullable', 'string', 'max:255'],
                'instrument_name'    => ['required', 'string', 'max:255'],
                'instrument_code'    => ['nullable', 'string', 'max:255', 'unique:calibration_instruments,instrument_code'],
                'serial_no'          => ['nullable', 'string', 'max:255'],
                'make'               => ['nullable', 'string', 'max:255'],
                'model'              => ['nullable', 'string', 'max:255'],
                'location'           => ['nullable', 'string', 'max:255'],
                'measurement_range'  => ['nullable', 'string', 'max:255'],
                'resolution'         => ['nullable', 'string', 'max:255'],
                'accuracy'           => ['nullable', 'string', 'max:255'],
                'last_calibrated_at' => ['nullable', 'date'],
                'calibration_due_at' => ['nullable', 'date'],
            ]);

            if ($validator->fails()) {
                $this->errors[] = [
                    'row'    => $rowNumber,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            CalibrationInstrument::create([
                'company_id'         => $this->companyId,
                'company_name'       => $data['company_name'] ?? null,
                'instrument_name'    => $data['instrument_name'],
                'instrument_code'    => $data['instrument_code'] ?? null,
                'serial_no'          => $data['serial_no'] ?? null,
                'make'               => $data['make'] ?? null,
                'model'              => $data['model'] ?? null,
                'location'           => $data['location'] ?? null,
                'measurement_range'  => $data['measurement_range'] ?? null,
                'resolution'         => $data['resolution'] ?? null,
                'accuracy'           => $data['accuracy'] ?? null,
                'last_calibrated_at' => $data['last_calibrated_at'] ?? null,
                'calibration_due_at' => $data['calibration_due_at'] ?? null,
            ]);

            $this->importedCount++;
        }
    }

    /**
     * Normalise a raw Excel row before validation.
     */
    private function castRow(array $data): array
    {
        // Fields that must be strings regardless of what Excel infers
        $stringFields = ['serial_no', 'resolution', 'accuracy', 'instrument_code'];
        foreach ($stringFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $data[$field] = (string) $data[$field];
            }
        }

        // Date fields
        $dateFields = ['last_calibrated_at', 'calibration_due_at'];
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

        // DateTime / Carbon object (PhpSpreadsheet can return these)
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // Numeric Excel serial date (e.g. 45829)
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        // Already a string — pass through; the validator will reject bad formats
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
            'company_name',
            'instrument_name',
            'instrument_code',
            'serial_no',
            'make',
            'model',
            'location',
            'measurement_range',
            'resolution',
            'accuracy',
            'last_calibrated_at',
            'calibration_due_at',
        ];
    }
}

<?php

namespace App\Services\Company;

use App\Imports\CalibrationInstrumentImport;
use App\Models\CalibrationInstrument;
use App\Models\Company;
use App\Models\User;
use App\Services\FileUploadService;
use Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

readonly class CalibrationInstrumentService
{
    public function __construct(
        private FileUploadService $fileUploadService,
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(CalibrationInstrument::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial('instrument_name'),
                AllowedFilter::partial('instrument_code'),
                AllowedFilter::partial('serial_no'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts(['instrument_name', 'calibration_due_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): CalibrationInstrument
    {
        $company = Company::find($data['company_id']);
        $user    = Auth::user();

        if (isset($user->company_id) && ($company->id !== $user->company_id)) {
            throw new UnauthorizedException("Company doesn't belong to you");
        }

        return CalibrationInstrument::create($data);
    }

    public function update(CalibrationInstrument $instrument, array $data): CalibrationInstrument
    {
        $instrument->update($data);

        return $instrument->fresh();
    }

    public function delete(CalibrationInstrument $instrument): void
    {
        // Clean up associated report file on delete
        $this->fileUploadService->delete($instrument->report_path);

        $instrument->delete();
    }

    /**
     * Upload calibration report for an instrument.
     */
    public function uploadReport(CalibrationInstrument $instrument, UploadedFile $file): CalibrationInstrument
    {
        $path = $this->fileUploadService->store(
            $file,
            "companies/{$instrument->company_id}/instruments/{$instrument->id}",
            'calibration_report.pdf'
        );

        $instrument->update(['report_path' => $path]);

        return $instrument->fresh();
    }

    /**
     * Download calibration report for an instrument.
     */
    public function downloadReport(CalibrationInstrument $instrument): StreamedResponse
    {
        return $this->fileUploadService->streamDownload(
            $instrument->report_path,
            "calibration_report_{$instrument->id}.pdf"
        );
    }

    /**
     * Delete calibration report for an instrument.
     */
    public function deleteReport(CalibrationInstrument $instrument): CalibrationInstrument
    {
        $this->fileUploadService->delete($instrument->report_path);

        $instrument->update(['report_path' => null]);

        return $instrument->fresh();
    }

    /**
     * Bulk import calibration instruments from an Excel/CSV file.
     * Returns import result with counts and any per-row errors.
     */
    public function import(UploadedFile $file, int $companyId): array
    {
        $import = new CalibrationInstrumentImport($companyId);

        Excel::import($import, $file);

        return [
            'imported' => $import->getImportedCount(),
            'skipped'  => count($import->getErrors()),
            'errors'   => $import->getErrors(),
        ];
    }
}

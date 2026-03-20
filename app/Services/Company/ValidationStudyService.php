<?php

namespace App\Services\Company;

use App\Imports\ValidationStudyImport;
use App\Models\Company;
use App\Models\ValidationStudy;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;

readonly class ValidationStudyService
{
    public function __construct(
        private FileUploadService $fileUploadService,
    ) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(ValidationStudy::forUser($user))
            ->allowedFilters([
                AllowedFilter::partial('area_type'),
                AllowedFilter::partial('location'),
                AllowedFilter::exact('qualification_type'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedSorts([
                'mapping_start_at',
                'mapping_due_at',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): ValidationStudy
    {
        $company = Company::find($data['company_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($company->id !== $user->company_id)) {
            throw new UnauthorizedException("Company doesn't belong to you");
        }

        return ValidationStudy::create($data);
    }

    public function update(ValidationStudy $study, array $data): ValidationStudy
    {
        $study->update($data);

        return $study->fresh();
    }

    public function delete(ValidationStudy $study): void
    {
        // Clean up associated report file on delete
        $this->fileUploadService->delete($study->report_path);

        $study->delete();
    }

    /**
     * Upload report for a validation study.
     */
    public function uploadReport(ValidationStudy $study, UploadedFile $file): ValidationStudy
    {
        $path = $this->fileUploadService->store(
            $file,
            "companies/{$study->company_id}/validation_studies/{$study->id}",
            'report.pdf'
        );

        $study->update(['report_path' => $path]);

        return $study->fresh();
    }

    /**
     * Download report for a validation study.
     */
    public function downloadReport(ValidationStudy $study): StreamedResponse
    {
        return $this->fileUploadService->streamDownload(
            $study->report_path,
            "validation_study_report_{$study->id}.pdf"
        );
    }

    /**
     * Delete report for a validation study.
     */
    public function deleteReport(ValidationStudy $study): ValidationStudy
    {
        $this->fileUploadService->delete($study->report_path);

        $study->update(['report_path' => null]);

        return $study->fresh();
    }

    /**
     * Bulk import validation studies from an Excel/CSV file.
     * Returns import result with counts and any per-row errors.
     */
    public function import(UploadedFile $file, int $companyId): array
    {
        $import = new ValidationStudyImport($companyId);

        Excel::import($import, $file);

        return [
            'imported' => $import->getImportedCount(),
            'skipped'  => count($import->getErrors()),
            'errors'   => $import->getErrors(),
        ];
    }
}

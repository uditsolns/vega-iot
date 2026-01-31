<?php

namespace App\Services\Company;

use App\Models\CalibrationInstrument;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class CalibrationInstrumentService
{
    public function __construct(private AuditService $auditService) {}

    public function list(array $filters, User $user): LengthAwarePaginator
    {
        return QueryBuilder::for(
            CalibrationInstrument::forUser($user)
        )
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
        $instrument = CalibrationInstrument::create($data);

        $this->auditService->log(
            'calibration_instrument.created',
            CalibrationInstrument::class,
            $instrument
        );

        return $instrument;
    }

    public function update(
        CalibrationInstrument $instrument,
        array $data
    ): CalibrationInstrument {
        $instrument->update($data);

        $this->auditService->log(
            'calibration_instrument.updated',
            CalibrationInstrument::class,
            $instrument
        );

        return $instrument->fresh();
    }

    public function delete(CalibrationInstrument $instrument): void
    {
        $instrument->delete();

        $this->auditService->log(
            'calibration_instrument.deleted',
            CalibrationInstrument::class,
            $instrument
        );
    }
}

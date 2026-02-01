<?php

namespace App\Services\Company;

use App\Models\CalibrationInstrument;
use App\Models\Company;
use App\Models\User;
use Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\UnauthorizedException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

readonly class CalibrationInstrumentService
{
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
        $company = Company::find($data['company_id']);
        $user = Auth::user();

        if (isset($user->company_id) && ($company->id !== $user->company_id)) {
            throw new UnauthorizedException("Company doesn't belong to you");
        }

        return CalibrationInstrument::create($data);
    }

    public function update(CalibrationInstrument $instrument, array $data): CalibrationInstrument {
        $instrument->update($data);

        return $instrument->fresh();
    }

    public function delete(CalibrationInstrument $instrument): void
    {
        $instrument->delete();
    }
}

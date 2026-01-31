<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationInstrument extends Model
{
    protected $fillable = [
        'company_id',
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
        'is_active',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts(): array
    {
        return [
            'last_calibrated_at' => 'date',
            'calibration_due_at' => 'date',
        ];
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', $user->company_id);
    }
}

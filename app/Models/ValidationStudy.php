<?php

namespace App\Models;

use App\Enums\ValidationQualificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class ValidationStudy extends Model
{
    protected $fillable = [
        'company_id',
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
        'report_path',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(["updated_at"])
            ->useLogName('area')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event validation study"));
    }

    protected function casts(): array
    {
        return [
            'qualification_type' => ValidationQualificationType::class,
            'mapping_start_at' => 'date',
            'mapping_end_at' => 'date',
            'mapping_due_at' => 'date',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', $user->company_id);
    }
}

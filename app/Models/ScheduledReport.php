<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use App\Enums\ScheduledReportFrequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledReport extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'frequency',
        'timezone',
        'time',
        'recipient_emails',
        'file_type',
        'format',
        'device_type',
        'data_formation',
        'interval',
        'is_active',
        'last_run_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => ScheduledReportFrequency::class,
            'file_type' => ReportFileType::class,
            'format' => ReportFormat::class,
            'device_type' => DeviceType::class,
            'data_formation' => ReportDataFormation::class,
            'recipient_emails' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'scheduled_report_devices')
            ->withPivot("created_at");
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ScheduledReportExecution::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', $user->company_id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDueForExecution(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}

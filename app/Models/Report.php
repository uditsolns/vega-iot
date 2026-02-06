<?php

namespace App\Models;

use App\Contracts\ReportableInterface;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Enums\ReportFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model implements ReportableInterface
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'device_id',
        'generated_by',
        'name',
        'file_type',
        'format',
        'data_formation',
        'interval',
        'from_datetime',
        'to_datetime',
        'generated_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where("company_id", $user->company_id);
    }

    protected function casts(): array
    {
        return [
            'file_type' => ReportFileType::class,
            'format' => ReportFormat::class,
            'data_formation' => ReportDataFormation::class,
            'from_datetime' => 'datetime',
            'to_datetime' => 'datetime',
            'generated_at' => 'datetime',
        ];
    }

    // REPORTABLE INTERFACE IMPLEMENTATION
    public function getReportName(): string
    {
        return $this->name;
    }

    public function getFileType(): ReportFileType
    {
        return $this->file_type;
    }

    public function getFormat(): ReportFormat
    {
        return $this->format;
    }

    public function getDataFormation(): ReportDataFormation
    {
        return $this->data_formation;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getFromDatetime(): Carbon
    {
        return $this->from_datetime;
    }

    public function getToDatetime(): Carbon
    {
        return $this->to_datetime;
    }

    public function getDeviceId(): int
    {
        return $this->device_id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }
}

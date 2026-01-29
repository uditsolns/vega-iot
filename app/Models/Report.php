<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'device_id',
        'generated_by',
        'name',
        'file_type',
        'report_format',
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
            'from_datetime' => 'datetime',
            'to_datetime' => 'datetime',
            'generated_at' => 'timestamp',
        ];
    }
}

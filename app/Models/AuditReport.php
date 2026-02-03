<?php

namespace App\Models;

use App\Enums\AuditReportType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'generated_by',
        'name',
        'type',
        'resource_id',
        'from_date',
        'to_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => AuditReportType::class,
            'from_date' => 'date',
            'to_date' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

        return $query->where('company_id', $user->company_id);
    }
}

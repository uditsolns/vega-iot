<?php

namespace App\Models;

use App\Enums\AlertResolutionOptionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertResolutionOption extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'label',
        'sort_order',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'type'      => AlertResolutionOptionType::class,
            'is_system' => 'boolean',
        ];
    }

    // RELATIONSHIPS

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // SCOPES

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->ofSystem()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('is_system', true)
                ->orWhere('company_id', $user->company_id);
        });
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}

<?php

namespace App\Models;

use App\Enums\AlertResolutionOptionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AlertResolutionOption extends Model
{
    protected $fillable = [
        'type',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type'      => AlertResolutionOptionType::class,
        ];
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeOfType(Builder $query, AlertResolutionOptionType|string $type): Builder
    {
        $value = $type instanceof AlertResolutionOptionType ? $type->value : $type;
        return $query->where('type', $value);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }
}

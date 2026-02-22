<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorConfiguration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_sensor_id',
        'min_critical',
        'max_critical',
        'min_warning',
        'max_warning',
        'effective_from',
        'effective_to',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'min_critical' => 'decimal:2',
            'max_critical' => 'decimal:2',
            'min_warning' => 'decimal:2',
            'max_warning' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function deviceSensor(): BelongsTo
    {
        return $this->belongsTo(DeviceSensor::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereNull('effective_to');
    }
}

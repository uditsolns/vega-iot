<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeviceSensor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'slot_number',
        'sensor_type_id',
        'is_enabled',
        'label',
        'accuracy',
        'resolution',
        'measurement_range',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'slot_number' => 'integer',
            'is_enabled' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function sensorType(): BelongsTo
    {
        return $this->belongsTo(SensorType::class);
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(SensorConfiguration::class)->orderByDesc('effective_from');
    }

    public function currentConfiguration(): HasOne
    {
        return $this->hasOne(SensorConfiguration::class)->whereNull('effective_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeForDevice(Builder $query, int $deviceId): Builder
    {
        return $query->where('device_id', $deviceId);
    }
}

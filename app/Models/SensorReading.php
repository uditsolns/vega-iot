<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $table = 'sensor_readings';

    // Composite primary key — Eloquent find() not used; use where() queries
    protected $primaryKey = ['device_id', 'device_sensor_id', 'recorded_at'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'device_sensor_id',
        'recorded_at',
        'received_at',
        'company_id',
        'area_id',
        'value_numeric',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at'   => 'datetime',
            'received_at'   => 'datetime',
            'value_numeric' => 'decimal:4',
            'metadata'      => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::unsetEventDispatcher(); // High-volume hypertable — disable model events
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function deviceSensor(): BelongsTo
    {
        return $this->belongsTo(DeviceSensor::class);
    }

    public function scopeForDevice(Builder $query, int $deviceId): Builder
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForSensor(Builder $query, int $deviceSensorId): Builder
    {
        return $query->where('device_sensor_id', $deviceSensorId);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForArea(Builder $query, int $areaId): Builder
    {
        return $query->where('area_id', $areaId);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $query->where('company_id', $user->company_id);

        if ($user->hasAreaRestrictions) {
            $query->whereIn('area_id', $user->allowedAreas);
        }

        return $query;
    }

    public function scopeInRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('recorded_at');
    }
}

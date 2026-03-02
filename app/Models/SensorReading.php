<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $table = 'sensor_readings';

    /**
     * WHY device_sensor_id instead of the full composite key:
     *
     * The physical primary key is (device_id, device_sensor_id, recorded_at).
     * However, Eloquent's ofMany() calls getKeyName() internally to build the
     * INNER JOIN that matches the MAX(recorded_at) subquery back to the full row.
     * When getKeyName() returns an array, Eloquent tries to use it as an array
     * offset and throws "Cannot access offset of type array on array".
     *
     * Setting $primaryKey to 'device_sensor_id' gives ofMany() the single string
     * it needs. The resulting join is:
     *
     *   INNER JOIN (
     *       SELECT device_sensor_id, MAX(recorded_at) AS recorded_at
     *       FROM sensor_readings WHERE device_sensor_id IN (...)
     *       GROUP BY device_sensor_id
     *   ) AS agg
     *     ON sensor_readings.device_sensor_id = agg.device_sensor_id
     *    AND sensor_readings.recorded_at      = agg.recorded_at
     *
     * (device_sensor_id, recorded_at) uniquely identifies a row in practice —
     * a sensor cannot have two readings at exactly the same timestamp.
     *
     * NOTE: Eloquent Model::find() is therefore unreliable on this model.
     * Always query with where() clauses. The existing comment in this model
     * already documented this; this change aligns the PK declaration with it.
     */
    protected $primaryKey = 'device_sensor_id';
    public $incrementing  = false;
    public $timestamps    = false;

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

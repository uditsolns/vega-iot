<?php

namespace App\Models;

use App\Enums\SensorDataType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SensorType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'unit',
        'data_type',
        'min_value',
        'max_value',
        'supports_threshold_config',
    ];

    protected function casts(): array
    {
        return [
            'data_type' => SensorDataType::class,
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
            'supports_threshold_config' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function deviceModelSlots(): HasMany
    {
        return $this->hasMany(DeviceModelSensorSlot::class, 'fixed_sensor_type_id');
    }

    public function deviceSensors(): HasMany
    {
        return $this->hasMany(DeviceSensor::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceModelSensorSlot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_model_id',
        'slot_number',
        'fixed_sensor_type_id',
        'label',
        'accuracy',
        'resolution',
        'measurement_range',
    ];

    protected function casts(): array
    {
        return [
            'slot_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function sensorType(): BelongsTo
    {
        return $this->belongsTo(SensorType::class, 'fixed_sensor_type_id');
    }
}

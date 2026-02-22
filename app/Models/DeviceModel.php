<?php

namespace App\Models;

use App\Enums\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
    protected $fillable = [
        'vendor',
        'model_name',
        'description',
        'max_slots',
        'is_configurable',
        'data_format',
    ];

    protected function casts(): array
    {
        return [
            'vendor' => Vendor::class,
            'max_slots' => 'integer',
            'is_configurable' => 'boolean',
            'data_format' => 'array',
        ];
    }

    public function sensorSlots(): HasMany
    {
        return $this->hasMany(DeviceModelSensorSlot::class)->orderBy('slot_number');
    }

    public function availableSensorTypes(): BelongsToMany
    {
        return $this->belongsToMany(SensorType::class, 'device_model_available_sensors');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function scopeByVendor(Builder $query, Vendor|string $vendor): Builder
    {
        $value = $vendor instanceof Vendor ? $vendor->value : $vendor;
        return $query->where('vendor', $value);
    }

    public function scopeConfigurable(Builder $query): Builder
    {
        return $query->where('is_configurable', true);
    }
}

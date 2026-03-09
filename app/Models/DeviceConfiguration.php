<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceConfiguration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'recording_interval',
        'sending_interval',
        'wifi_ssid',
        'wifi_password',
        'wifi_mode',
        'timezone_offset_minutes',
        'effective_from',
        'effective_to',
        'last_synced_at',
        'updated_by',
    ];

    protected $hidden = ['wifi_password'];

    protected function casts(): array
    {
        return [
            'recording_interval' => 'integer',
            'sending_interval' => 'integer',
            'timezone_offset_minutes' => 'integer',
            'wifi_password' => 'encrypted',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'temp_min_critical',
        'temp_max_critical',
        'temp_min_warning',
        'temp_max_warning',
        'humidity_min_critical',
        'humidity_max_critical',
        'humidity_min_warning',
        'humidity_max_warning',
        'temp_probe_min_critical',
        'temp_probe_max_critical',
        'temp_probe_min_warning',
        'temp_probe_max_warning',
        'record_interval',
        'send_interval',
        'wifi_ssid',
        'wifi_password',
        'active_temp_sensor',
        'is_current',
        'updated_by',
    ];

    protected $hidden = [
        'wifi_password',
    ];

    protected function casts(): array
    {
        return [
            'temp_min_critical' => 'decimal:2',
            'temp_max_critical' => 'decimal:2',
            'temp_min_warning' => 'decimal:2',
            'temp_max_warning' => 'decimal:2',
            'humidity_min_critical' => 'decimal:2',
            'humidity_max_critical' => 'decimal:2',
            'humidity_min_warning' => 'decimal:2',
            'humidity_max_warning' => 'decimal:2',
            'temp_probe_min_critical' => 'decimal:2',
            'temp_probe_max_critical' => 'decimal:2',
            'temp_probe_min_warning' => 'decimal:2',
            'temp_probe_max_warning' => 'decimal:2',
            'record_interval' => 'integer',
            'send_interval' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to get only current configurations
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}

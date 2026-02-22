<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Device extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'device_uid',
        'device_code',
        'device_model_id',
        'firmware_version',
        'company_id',
        'area_id',
        'device_name',
        'status',
        'is_active',
        'last_reading_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['last_reading_at', 'status', 'updated_at'])
            ->useLogName('device')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event device \"$this->device_code\""));
    }

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'is_active' => 'boolean',
            'last_reading_at' => 'datetime',
        ];
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(DeviceSensor::class)->orderBy('slot_number');
    }

    public function enabledSensors(): HasMany
    {
        return $this->hasMany(DeviceSensor::class)->where('is_enabled', true)->orderBy('slot_number');
    }

    public function currentConfiguration(): HasOne
    {
        return $this->hasOne(DeviceConfiguration::class)->whereNull('effective_to');
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(DeviceConfiguration::class)->orderByDesc('effective_from');
    }

    public function configurationRequests(): HasMany
    {
        return $this->hasMany(DeviceConfigurationRequest::class);
    }

    public function pendingConfigurationRequest(): HasOne
    {
        return $this->hasOne(DeviceConfigurationRequest::class)
            ->where('status', \App\Enums\ConfigRequestStatus::Pending)
            ->orderByDesc('priority')
            ->orderBy('created_at');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $query->where('company_id', $user->company_id);

        if ($user->hasAreaRestrictions && $user->company_id) {
            $query->where(function ($q) use ($user) {
                $q->whereIn('area_id', $user->allowedAreas)->orWhereNull('area_id');
            });
        }

        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemInventory(Builder $query): Builder
    {
        return $query->whereNull('company_id')->whereNull('area_id');
    }

    public function scopeCompanyInventory(Builder $query): Builder
    {
        return $query->whereNotNull('company_id')->whereNull('area_id');
    }

    public function scopeDeployed(Builder $query): Builder
    {
        return $query->whereNotNull('area_id');
    }

    public function scopeByStatus(Builder $query, DeviceStatus|string $status): Builder
    {
        $value = $status instanceof DeviceStatus ? $status->value : $status;
        return $query->where('status', $value);
    }

    // ========================================
    // HELPERS
    // ========================================

    public function isSystemInventory(): bool
    {
        return is_null($this->company_id) && is_null($this->area_id);
    }

    public function isCompanyInventory(): bool
    {
        return !is_null($this->company_id) && is_null($this->area_id);
    }

    public function isDeployed(): bool
    {
        return !is_null($this->area_id);
    }

    public function getLocationPath(): string
    {
        if (!$this->isDeployed() || !$this->area) {
            return 'Unassigned';
        }

        $this->area->load('hub.location');
        $location = $this->area->hub->location->name ?? '';
        $hub = $this->area->hub->name ?? '';
        $area = $this->area->name ?? '';

        return $location && $hub && $area ? "{$location} → {$hub} → {$area}" : 'Unassigned';
    }
}

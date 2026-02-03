<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Device extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        "device_uid",
        "device_code",
        "make",
        "model",
        "type",
        "firmware_version",
        "temp_resolution",
        "temp_accuracy",
        "humidity_resolution",
        "humidity_accuracy",
        "temp_probe_resolution",
        "temp_probe_accuracy",
        "company_id",
        "area_id",
        "device_name",
        "status",
        "is_active",
        "last_reading_at",
        "api_key",
    ];

    protected $hidden = ["api_key"];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['company_id', 'area_id', 'device_name',  'last_reading_at', 'status', 'updated_at', 'api_key'])
            ->useLogName('device')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event device \"$this->device_code\""));
    }

    protected function casts(): array
    {
        return [
            "type" => DeviceType::class,
            "status" => DeviceStatus::class,
            "temp_resolution" => "decimal:2",
            "temp_accuracy" => "decimal:2",
            "humidity_resolution" => "decimal:2",
            "humidity_accuracy" => "decimal:2",
            "temp_probe_resolution" => "decimal:2",
            "temp_probe_accuracy" => "decimal:2",
            "is_active" => "boolean",
            "last_reading_at" => "datetime",
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            if (empty($device->api_key)) {
                $device->api_key = Str::random(64);
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function currentConfiguration(): HasOne
    {
        return $this->hasOne(DeviceConfiguration::class)->where(
            "is_current",
            true,
        );
    }

    public function configurations(): HasMany
    {
        return $this->hasMany(DeviceConfiguration::class)->orderBy(
            "created_at",
            "desc",
        );
    }

    public function latestReading(): HasOne
    {
        return $this->hasOne(DeviceReading::class)
            ->orderByDesc("recorded_at")
            ->limit(1);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope to filter devices based on user's company and area restrictions
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Filter by company
        $query->where(function ($q) use ($user) {
            $q->where("company_id", $user->company_id);
        });

        // Apply area restrictions if user has them
        if ($user->hasAreaRestrictions && $user->company_id) {
            $query->where(function ($q) use ($user) {
                $q->whereIn("area_id", $user->allowedAreas)->orWhereNull(
                    "area_id",
                ); // Can see undeployed devices in their company
            });
        }

        return $query;
    }

    /**
     * Scope for active devices
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope for system inventory (unassigned devices)
     */
    public function scopeSystemInventory(Builder $query): Builder
    {
        return $query->whereNull("company_id")->whereNull("area_id");
    }

    /**
     * Scope for company inventory (assigned to company but not deployed)
     */
    public function scopeCompanyInventory(Builder $query): Builder
    {
        return $query->whereNotNull("company_id")->whereNull("area_id");
    }

    /**
     * Scope for deployed devices (assigned to area)
     */
    public function scopeDeployed(Builder $query): Builder
    {
        return $query->whereNotNull("area_id");
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus(
        Builder $query,
        DeviceStatus|string $status,
    ): Builder {
        $statusValue =
            $status instanceof DeviceStatus ? $status->value : $status;
        return $query->where("status", $statusValue);
    }

    /**
     * Check if device is in system inventory
     */
    public function isSystemInventory(): bool
    {
        return is_null($this->company_id) && is_null($this->area_id);
    }

    /**
     * Check if device is in company inventory
     */
    public function isCompanyInventory(): bool
    {
        return !is_null($this->company_id) && is_null($this->area_id);
    }

    /**
     * Check if device is deployed
     */
    public function isDeployed(): bool
    {
        return !is_null($this->area_id);
    }

    /**
     * Get full location path for the device
     */
    public function getLocationPath(): string
    {
        if (!$this->isDeployed() || !$this->area) {
            return "Unassigned";
        }

        // Load relationships if not already loaded
        $this->area->load("hub.location");

        $location = $this->area->hub->location->name ?? "";
        $hub = $this->area->hub->name ?? "";
        $area = $this->area->name ?? "";

        if ($location && $hub && $area) {
            return "{$location} → {$hub} → {$area}";
        }

        return "Unassigned";
    }

    // ========================================
    // READING HELPER METHODS
    // ========================================

    /**
     * Get the latest reading for this device
     */
    public function getLatestReading(): ?DeviceReading
    {
        return DeviceReading::forDevice($this->id)->latest()->first();
    }

    /**
     * Check if device has a recent reading within threshold
     */
    public function hasRecentReading(int $minutes = 30): bool
    {
        if (!$this->last_reading_at) {
            return false;
        }

        return $this->last_reading_at->diffInMinutes(now()) <= $minutes;
    }

    /**
     * Get reading statistics for a time period
     * Uses DB facade for optimal performance with large datasets
     */
    public function getReadingStats(
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
    ): array {
        $stats = DB::table("device_readings")
            ->where("device_id", $this->id)
            ->where("recorded_at", ">=", $from)
            ->where("recorded_at", "<=", $to)
            ->selectRaw(
                '
                COUNT(*) as reading_count,
                AVG(temperature) as avg_temp,
                MIN(temperature) as min_temp,
                MAX(temperature) as max_temp,
                AVG(humidity) as avg_humidity,
                MIN(humidity) as min_humidity,
                MAX(humidity) as max_humidity,
                AVG(temp_probe) as avg_temp_probe,
                MIN(temp_probe) as min_temp_probe,
                MAX(temp_probe) as max_temp_probe,
                AVG(battery_percentage) as avg_battery,
                MIN(battery_percentage) as min_battery
            ',
            )
            ->first();

        return $stats ? (array) $stats : [];
    }
}

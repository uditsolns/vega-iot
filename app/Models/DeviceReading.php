<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeviceReading extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = "device_readings";

    /**
     * The primary key for the model.
     *
     * @var array
     */
    protected $primaryKey = ["device_id", "recorded_at"];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "device_id",
        "recorded_at",
        "received_at",
        "company_id",
        "location_id",
        "hub_id",
        "area_id",
        "temperature",
        "humidity",
        "temp_probe",
        "battery_voltage",
        "battery_percentage",
        "wifi_signal_strength",
        "firmware_version",
        "raw_payload",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "recorded_at" => "datetime",
            "received_at" => "datetime",
            "temperature" => "decimal:2",
            "humidity" => "decimal:2",
            "temp_probe" => "decimal:2",
            "battery_voltage" => "decimal:2",
            "battery_percentage" => "integer",
            "wifi_signal_strength" => "integer",
            "raw_payload" => "array",
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Disable Eloquent events for performance optimization
        // This is a high-volume time-series table - no observers needed
        static::unsetEventDispatcher();
    }

    /**
     * Scope a query to filter by device ID.
     */
    public function scopeForDevice(Builder $query, int $deviceId): Builder
    {
        return $query->where("device_id", $deviceId);
    }

    public const CREATED_AT = "recorded_at";

    /**
     * Scope a query to filter by time range.
     */
    public function scopeInRange(
        Builder $query,
        string $from,
        string $to,
    ): Builder {
        return $query->whereBetween("recorded_at", [$from, $to]);
    }

    /**
     * Scope a query to order by latest readings first.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy("recorded_at", "desc");
    }

    /**
     * Scope a query to filter by company ID.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where("company_id", $companyId);
    }

    /**
     * Scope a query to filter by area ID.
     */
    public function scopeForArea(Builder $query, int $areaId): Builder
    {
        return $query->where("area_id", $areaId);
    }

    /**
     * Scope a query to filter by location ID.
     */
    public function scopeForLocation(Builder $query, int $locationId): Builder
    {
        return $query->where("location_id", $locationId);
    }

    /**
     * Scope a query to filter readings based on user's company and area restrictions
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Filter by company
        $query->where("company_id", $user->company_id);

        // Apply area restrictions if user has them
        if ($user->hasAreaRestrictions) {
            $query->whereIn("area_id", $user->allowedAreas);
        }

        return $query;
    }
}

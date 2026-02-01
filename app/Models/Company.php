<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Company extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "name",
        "client_name",
        "email",
        "phone",
        "billing_address",
        "shipping_address",
        "gst_number",
        "is_active",
        "is_hierarchy_enabled",
        "is_csv_export_enabled",
        "is_device_config_enabled",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
            "is_hierarchy_enabled" => "boolean",
            "is_csv_export_enabled" => "boolean",
            "is_device_config_enabled" => "boolean",
            "deleted_at" => "datetime",
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->useLogName('company')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event company \"$this->name\""));
    }

    /**
     * Get the users for the company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the roles for the company.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Get the locations for the company.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get the devices for the company.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where("is_active", true);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where("id", $user->company_id);
    }
}

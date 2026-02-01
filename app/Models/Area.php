<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Area extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "hub_id",
        "name",
        "description",
        "is_active",
        "alert_email_enabled",
        "alert_sms_enabled",
        "alert_voice_enabled",
        "alert_push_enabled",
        "alert_warning_enabled",
        "alert_critical_enabled",
        "alert_back_in_range_enabled",
        "alert_device_status_enabled",
        "acknowledged_alert_notification_interval",
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
            "alert_email_enabled" => "boolean",
            "alert_sms_enabled" => "boolean",
            "alert_voice_enabled" => "boolean",
            "alert_push_enabled" => "boolean",
            "alert_warning_enabled" => "boolean",
            "alert_critical_enabled" => "boolean",
            "alert_back_in_range_enabled" => "boolean",
            "alert_device_status_enabled" => "boolean",
            "acknowledged_alert_notification_interval" => "integer",
            "deleted_at" => "datetime",
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['hub_id', 'name', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('area')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event area \"$this->name\""));
    }

    /**
     * Get the hub that owns the area.
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    /**
     * Get the devices deployed to this area.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    /**
     * Scope a query to only include active areas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where("is_active", true);
    }

    /**
     * Scope a query to filter areas based on user permissions.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas("hub.location", function (Builder $q) use (
            $user,
        ) {
            $q->where("company_id", $user->company_id);
        });
    }
}

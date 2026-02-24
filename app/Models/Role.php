<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class Role extends Model
{
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "company_id",
        "name",
        "description",
        "hierarchy_level",
        "is_system_role",
        "is_editable",
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_system_role" => "boolean",
            "is_editable" => "boolean",
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'hierarchy_level', 'company_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('role')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event role \"$this->name\""));
    }

    /**
     * Get the company that owns the role.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the permissions for the role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, "role_permissions");
    }

    /**
     * Get the users that have this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder {
        if ($user->ofSystem()) {
            return $query;
        }

        return $query->where("is_system_role", true)
            ->orWhere("company_id", $user->company_id);
    }

    /**
     * Scope a query to only include system roles.
     */
    public function scopeSystemRoles(Builder $query): Builder
    {
        return $query->where("is_system_role", true);
    }

    /**
     * Scope a query to only include editable roles.
     */
    public function scopeEditableRoles(Builder $query): Builder
    {
        return $query->where("is_editable", true);
    }
}

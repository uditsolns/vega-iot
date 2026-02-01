<?php

namespace App\Models;

use App\Enums\UserWorkMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "company_id",
        "role_id",
        "email",
        "password",
        "first_name",
        "last_name",
        "phone",
        "is_active",
        "dark_theme_enabled",
        "work_mode",
        "last_login_at",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "is_active" => "boolean",
            "password" => "hashed",
            "dark_theme_enabled" => "boolean",
            "work_mode" => UserWorkMode::class,
            "last_login_at" => "datetime",
            "deleted_at" => "datetime",
        ];
    }

    /**
     * Get activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['email', 'first_name', 'last_name', 'phone', 'work_mode'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName("user")
            ->setDescriptionForEvent(fn($event) => ucfirst("{$event} user \"$this->email\""));
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->put('email', $this->email);
    }

    /**
     * Get the company that owns the user.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the role that the user has.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user-specific permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            "user_permissions",
        )->withPivot("granted_at", "granted_by");
    }

    /**
     * Get the area access restrictions for the user.
     */
    public function areaAccess(): HasMany
    {
        return $this->hasMany(UserAreaAccess::class);
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions ?? []);
    }

    /**
     * Get all permissions (role + user-specific).
     */
    public function getAllPermissions(): Collection
    {
        $rolePermissions = $this->role ? $this->role->permissions : collect();
        $userPermissions = $this->permissions;
        return $rolePermissions->merge($userPermissions)->unique("id");
    }

    /**
     * Check if the user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->company_id === null &&
            $this->role?->hierarchy_level === 1;
    }

    /**
     * Scope a query to filter by user's company.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where("company_id", $user->company_id);
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where("is_active", true);
    }
}

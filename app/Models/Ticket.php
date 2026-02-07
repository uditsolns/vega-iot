<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'user_id',
        'company_id',
        'device_id',
        'location_id',
        'hub_id',
        'area_id',
        'subject',
        'description',
        'reason',
        'status',
        'priority',
        'assigned_to',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['priority', 'subject', 'description', 'status', 'assigned_to'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ticket')
            ->setDescriptionForEvent(fn($event) => ucfirst("$event ticket \"$this->subject\""));
    }

    /**
     * Relationships
     */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Scopes
     */

    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('company_id', $user->company_id);
    }

    public function scopeByStatus(Builder $query, string|TicketStatus $status): Builder
    {
        $statusValue = $status instanceof TicketStatus ? $status->value : $status;
        return $query->where('status', $statusValue);
    }

    public function scopeByPriority(Builder $query, string|TicketPriority $priority): Builder
    {
        $priorityValue = $priority instanceof TicketPriority ? $priority->value : $priority;
        return $query->where('priority', $priorityValue);
    }

    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where('assigned_to', $user->id);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::Reopened->value,
        ]);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TicketStatus::Resolved->value,
            TicketStatus::Closed->value,
        ]);
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Accessors
     */

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getPriorityLabelAttribute(): string
    {
        return $this->priority->label();
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status->isOpen();
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Lifecycle Methods
     */

    /**
     * Assign ticket to a user
     */
    public function assign(User $user): bool
    {
        $this->assigned_to = $user->id;

        // Auto-update status to in_progress if currently open
        if ($this->status === TicketStatus::Open) {
            $this->status = TicketStatus::InProgress;
        }

        return $this->save();
    }

    /**
     * Mark ticket as resolved
     */
    public function resolve(User $resolvedBy): bool
    {
        if (!$this->status->canTransitionTo(TicketStatus::Resolved)) {
            return false;
        }

        $this->status = TicketStatus::Resolved;
        $this->resolved_at = now();
        $this->resolved_by = $resolvedBy->id;

        return $this->save();
    }

    /**
     * Close the ticket
     */
    public function close(User $closedBy): bool
    {
        if (!$this->status->canTransitionTo(TicketStatus::Closed)) {
            return false;
        }

        $this->status = TicketStatus::Closed;
        $this->closed_at = now();
        $this->closed_by = $closedBy->id;

        // Set resolved_at if not already set
        if (!$this->resolved_at) {
            $this->resolved_at = now();
            $this->resolved_by = $closedBy->id;
        }

        return $this->save();
    }

    /**
     * Reopen the ticket
     */
    public function reopen(): bool
    {
        if (!$this->status->canTransitionTo(TicketStatus::Reopened)) {
            return false;
        }

        $this->status = TicketStatus::Reopened;
        $this->resolved_at = null;
        $this->resolved_by = null;
        $this->closed_at = null;
        $this->closed_by = null;

        return $this->save();
    }

    /**
     * Check if user is the ticket creator
     */
    public function isCreatedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Check if user is assigned to the ticket
     */
    public function isAssignedTo(User $user): bool
    {
        return $this->assigned_to === $user->id;
    }

    /**
     * Check if user can see internal comments
     * Only VEGA's internal support team can see internal comments
     * Customer users (even if they created the ticket) CANNOT see internal comments
     */
    public function canUserSeeInternalComments(User $user): bool
    {
        // Customer users (those with company_id) can NEVER see internal comments
        if ($user->company_id !== null) {
            return false;
        }

        // VEGA's super admins can see all internal comments
        if ($user->isSuperAdmin()) {
            return true;
        }

        // VEGA's support staff assigned to ticket can see internal comments
        if ($this->isAssignedTo($user)) {
            return true;
        }

        // VEGA's users with permission can see internal comments
        if ($user->hasPermission('tickets.view_internal_comments')) {
            return true;
        }

        return false;
    }
}

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

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

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
        'closed_at',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
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
     * Methods
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

    public function changeStatus(string|TicketStatus $status): bool
    {
        $newStatus = $status instanceof TicketStatus ? $status : TicketStatus::from($status);

        $this->status = $newStatus;

        // Set timestamps based on status
        if ($newStatus === TicketStatus::Resolved && !$this->resolved_at) {
            $this->resolved_at = now();
        }

        if ($newStatus === TicketStatus::Closed && !$this->closed_at) {
            $this->closed_at = now();
        }

        // Clear timestamps if reopened
        if ($newStatus === TicketStatus::Reopened) {
            $this->resolved_at = null;
            $this->closed_at = null;
        }

        return $this->save();
    }

    public function close(): bool
    {
        return $this->changeStatus(TicketStatus::Closed);
    }

    public function resolve(): bool
    {
        return $this->changeStatus(TicketStatus::Resolved);
    }

    public function reopen(): bool
    {
        return $this->changeStatus(TicketStatus::Reopened);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'comment_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
        'uploaded_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relationships
     */

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'comment_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Accessors
     */

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        } else {
            return '0 bytes';
        }
    }

    public function getDownloadUrlAttribute(): string
    {
        return $this->getDownloadUrl();
    }

    /**
     * Methods
     */

    public function getDownloadUrl(): string
    {
        // Generate signed URL for secure download
        return route('api.v1.tickets.attachments.download', [
            'ticket' => $this->ticket_id,
            'attachment' => $this->id,
        ]);
    }

    public function getStoragePath(): string
    {
        return Storage::disk('private')->path($this->file_path);
    }

    public function exists(): bool
    {
        return Storage::disk('private')->exists($this->file_path);
    }

    public function delete(): ?bool
    {
        // Delete physical file
        if ($this->exists()) {
            Storage::disk('private')->delete($this->file_path);
        }

        // Delete database record
        return parent::delete();
    }
}

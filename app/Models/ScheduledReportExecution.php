<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledReportExecution extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scheduled_report_id',
        'executed_at',
        'status',
        'reports_generated',
        'reports_failed',
        'error_message',
        'execution_details',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'execution_details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scheduledReport(): BelongsTo
    {
        return $this->belongsTo(ScheduledReport::class);
    }
}

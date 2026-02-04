<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ScheduledReportDevice extends Pivot
{
    protected $table = 'scheduled_report_devices';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}

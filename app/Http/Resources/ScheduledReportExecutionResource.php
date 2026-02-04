<?php

namespace App\Http\Resources;

use App\Models\ScheduledReportExecution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScheduledReportExecution */
class ScheduledReportExecutionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'executed_at' => $this->executed_at,
            'status' => $this->status,
            'reports_generated' => $this->reports_generated,
            'reports_failed' => $this->reports_failed,
            'error_message' => $this->error_message,
            'execution_details' => $this->execution_details,
            'created_at' => $this->created_at,

            'scheduled_report_id' => $this->scheduled_report_id,

            'scheduled_report' => new ScheduledReportResource($this->whenLoaded('scheduledReport')),
        ];
    }
}

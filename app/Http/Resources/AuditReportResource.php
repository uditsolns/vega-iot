<?php

namespace App\Http\Resources;

use App\Models\AuditReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditReport */
class AuditReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'generated_at' => $this->generated_at,

            'company_id' => $this->company_id,
            'generated_by' => $this->generated_by,

            'company' => new CompanyResource($this->whenLoaded('company')),
            'generated_by_user' => new UserResource($this->whenLoaded('generatedBy')),
        ];
    }
}

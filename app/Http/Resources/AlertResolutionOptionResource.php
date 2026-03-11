<?php

namespace App\Http\Resources;

use App\Models\AlertResolutionOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AlertResolutionOption */
class AlertResolutionOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type->value,
            'type_label' => $this->type->label(),
            'label'      => $this->label,
            'sort_order' => $this->sort_order,
        ];
    }
}

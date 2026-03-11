<?php

namespace App\Services\Alert;

use App\Enums\AlertResolutionOptionType;
use App\Models\AlertResolutionOption;
use Illuminate\Database\Eloquent\Collection;

class AlertResolutionOptionService
{
    /**
     * All active options grouped by type — used to populate dropdowns.
     */
    public function listGrouped(): array
    {
        return AlertResolutionOption::ordered()
            ->get()
            ->groupBy(fn($o) => $o->type->value)
            ->map(fn($items) => $items->values())
            ->toArray();
    }

    public function create(array $data): AlertResolutionOption
    {
        return AlertResolutionOption::create([
            'type'       => $data['type'],
            'label'      => $data['label'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function update(AlertResolutionOption $option, array $data): AlertResolutionOption
    {
        $option->update(array_filter([
            'label'      => $data['label'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn($v) => !is_null($v)));

        return $option->fresh();
    }

    public function delete(AlertResolutionOption $option): void
    {
        $option->delete();
    }
}

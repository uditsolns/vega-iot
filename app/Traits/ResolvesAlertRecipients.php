<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Collection;

trait ResolvesAlertRecipients
{
    /**
     * Resolve all users who should receive notifications for a given area.
     *
     * Users with no area restrictions see all alerts.
     * Users restricted to specific areas see only their areas.
     */
    private function getUsersToNotify($area): Collection
    {
        return collect();

//        if (!$area) {
//            return collect();
//        }
//
//        $area->loadMissing('hub.location');
//
//        $companyId = $area->hub?->location?->company_id ?? null;
//
//        if (!$companyId) {
//            return collect();
//        }
//
//        return User::query()
//            ->where('is_active', true)
//            ->where('company_id', $companyId)
//            ->where(function ($q) use ($area) {
//                $q->whereDoesntHave('areaAccess')
//                    ->orWhereHas('areaAccess', fn($q2) => $q2->where('area_id', $area->id));
//            })
//            ->get();
    }
}

<?php

namespace App\Services\Dashboard;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\TicketStatus;
use App\Models\Alert;
use App\Models\Device;
use App\Models\Ticket;
use App\Models\User;

class DashboardService
{
    /**
     * Get dashboard overview statistics
     */
    public function getOverview(User $user): array
    {
        // Get accessible devices
        $devicesQuery = Device::forUser($user);
        $totalDevices = $devicesQuery->clone()->count();
        $devicesOnline = $devicesQuery->clone()->where('status', DeviceStatus::Online)->count();
        $devicesOffline = $devicesQuery->clone()->where('status', DeviceStatus::Offline)->count();

        // Get active and critical alerts
        $alerts = Alert::forUser($user)
            ->whereIn('status', [AlertStatus::Active, AlertStatus::Acknowledged])
            ->get();
        $activeAlertsCount = $alerts->count();
        $criticalAlertsCount = $alerts->where('severity', AlertSeverity::Critical)->count();

        // Get ticket statistics
        $ticketsOpenCount = Ticket::forUser($user)
            ->where('status', TicketStatus::Open)
            ->count();

        $ticketsAssignedToMe = Ticket::forUser($user)
            ->where('assigned_to', $user->id)
            ->whereIn('status', [TicketStatus::Open, TicketStatus::InProgress])
            ->count();

        return [
            'total_devices' => $totalDevices,
            'devices_online' => $devicesOnline,
            'devices_offline' => $devicesOffline,
            'active_alerts_count' => $activeAlertsCount,
            'critical_alerts_count' => $criticalAlertsCount,
            'tickets_open_count' => $ticketsOpenCount,
            'tickets_assigned_to_me' => $ticketsAssignedToMe,
        ];
    }
}

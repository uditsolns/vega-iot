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
        $devicesQuery = Device::forUser($user);

        $totalDevices  = $devicesQuery->clone()->count();
        $devicesOnline = $devicesQuery->clone()->where('status', DeviceStatus::Online)->count();
        $devicesOffline = $devicesQuery->clone()->where('status', DeviceStatus::Offline)->count();

        // Single query: distinct device+severity pairs for all open alerts
        $openStatuses = [AlertStatus::Active->value, AlertStatus::Acknowledged->value];

        $alertedDevices = Alert::forUser($user)
            ->whereIn('status', $openStatuses)
            ->selectRaw('device_id, severity')
            ->distinct()
            ->get();

        $warningDeviceCount  = $alertedDevices->where('severity', AlertSeverity::Warning->value)->pluck('device_id')->unique()->count();
        $criticalDeviceCount = $alertedDevices->where('severity', AlertSeverity::Critical->value)->pluck('device_id')->unique()->count();
        $anyAlertedCount     = $alertedDevices->pluck('device_id')->unique()->count();

        return [
            'total_devices'        => $totalDevices,
            'devices_online'       => $devicesOnline,
            'devices_offline'      => $devicesOffline,
            'devices_good'         => $totalDevices - $anyAlertedCount,
            'devices_with_warning' => $warningDeviceCount,
            'devices_with_critical' => $criticalDeviceCount,
        ];
    }
}

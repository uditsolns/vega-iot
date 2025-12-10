<?php

namespace App\Services\Dashboard;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\DeviceStatus;
use App\Enums\TicketStatus;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard overview statistics
     */
    public function getOverview(User $user): array
    {
        // Get accessible devices
        $devices = Device::forUser($user)->get();
        $totalDevices = $devices->count();
        $devicesOnline = $devices->where('status', DeviceStatus::Online)->count();
        $devicesOffline = $devices->where('status', DeviceStatus::Offline)->count();
        $devicesMaintenance = $devices->where('status', DeviceStatus::Maintenance)->count();

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

        // Get readings today
        $deviceIds = $devices->pluck('id')->toArray();
        $readingsTodayCount = 0;

        if (!empty($deviceIds)) {
            $readingsTodayCount = DeviceReading::whereIn('device_id', $deviceIds)
                ->whereDate('recorded_at', today())
                ->count();
        }

        // Calculate compliance rate for this week
        $complianceRateThisWeek = $this->calculateWeeklyComplianceRate($user, $deviceIds);

        return [
            'total_devices' => $totalDevices,
            'devices_online' => $devicesOnline,
            'devices_offline' => $devicesOffline,
            'devices_maintenance' => $devicesMaintenance,
            'active_alerts_count' => $activeAlertsCount,
            'critical_alerts_count' => $criticalAlertsCount,
            'tickets_open_count' => $ticketsOpenCount,
            'tickets_assigned_to_me' => $ticketsAssignedToMe,
            'readings_today_count' => $readingsTodayCount,
            'compliance_rate_this_week' => $complianceRateThisWeek,
        ];
    }

    /**
     * Calculate weekly compliance rate
     */
    private function calculateWeeklyComplianceRate(User $user, array $deviceIds): float
    {
        if (empty($deviceIds)) {
            return 0.0;
        }

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        // Get devices with configurations
        $devices = Device::whereIn('id', $deviceIds)
            ->with('currentConfiguration')
            ->get();

        // Get readings for this week
        $readings = DeviceReading::whereIn('device_id', $deviceIds)
            ->whereBetween('recorded_at', [$weekStart, $weekEnd])
            ->get();

        if ($readings->isEmpty()) {
            return 0.0;
        }

        $totalReadings = $readings->count();
        $compliantReadings = 0;

        foreach ($readings as $reading) {
            $device = $devices->firstWhere('id', $reading->device_id);
            $config = $device?->currentConfiguration;

            if (!$config) {
                continue;
            }

            $inCompliance = true;

            // Check temperature
            if ($reading->temperature !== null) {
                if (($config->temp_min !== null && $reading->temperature < $config->temp_min) ||
                    ($config->temp_max !== null && $reading->temperature > $config->temp_max)) {
                    $inCompliance = false;
                }
            }

            // Check humidity
            if ($reading->humidity !== null) {
                if (($config->humidity_min !== null && $reading->humidity < $config->humidity_min) ||
                    ($config->humidity_max !== null && $reading->humidity > $config->humidity_max)) {
                    $inCompliance = false;
                }
            }

            if ($inCompliance) {
                $compliantReadings++;
            }
        }

        return round(($compliantReadings / $totalReadings) * 100, 2);
    }

    /**
     * Get device status breakdown
     */
    public function getDeviceStatus(User $user): array
    {
        $devices = Device::forUser($user)
            ->with(['area.hub.location'])
            ->get();

        // By status
        $byStatus = [
            'online' => $devices->where('status', DeviceStatus::Online)->count(),
            'offline' => $devices->where('status', DeviceStatus::Offline)->count(),
            'maintenance' => $devices->where('status', DeviceStatus::Maintenance)->count(),
            'decommissioned' => $devices->where('status', DeviceStatus::Decommissioned)->count(),
        ];

        // By type
        $byType = $devices->groupBy(fn($device) => $device->type->value)
            ->map(fn($group) => $group->count())
            ->toArray();

        // By location (top 10)
        $byLocation = $devices->groupBy(fn($device) => $device->area?->hub?->location_id)
            ->map(function ($group) {
                $location = $group->first()->area?->hub?->location;
                return [
                    'location_id' => $location?->id,
                    'location_name' => $location?->name,
                    'device_count' => $group->count(),
                ];
            })
            ->filter(fn($item) => $item['location_id'] !== null)
            ->sortByDesc('device_count')
            ->take(10)
            ->values()
            ->toArray();

        // Recently offline (last 24 hours)
        $recentlyOffline = $devices
            ->where('status', DeviceStatus::Offline)
            ->where('last_seen_at', '>', now()->subHours(24))
            ->map(fn($device) => [
                'id' => $device->id,
                'name' => $device->name,
                'area_name' => $device->area?->name,
                'location_name' => $device->area?->hub?->location?->name,
                'last_seen_at' => $device->last_seen_at?->toISOString(),
            ])
            ->values()
            ->toArray();

        return [
            'by_status' => $byStatus,
            'by_type' => $byType,
            'by_location' => $byLocation,
            'recently_offline' => $recentlyOffline,
        ];
    }

    /**
     * Get active alerts with scoping and ordering
     */
    public function getActiveAlerts(User $user, int $limit = 10): Collection
    {
        return Alert::forUser($user)
            ->whereIn('status', [AlertStatus::Active, AlertStatus::Acknowledged])
            ->with(['device.area'])
            ->orderByRaw("CASE
                WHEN severity = 'critical' THEN 1
                WHEN severity = 'warning' THEN 2
                ELSE 3
            END")
            ->orderBy('triggered_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activity from audit logs
     */
    public function getRecentActivity(User $user, int $limit = 20): Collection
    {
        $query = AuditLog::query()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        // Apply company scoping for non-super admins
        if (!$user->isSuperAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        // Filter for relevant activity types
        $query->whereIn('event', [
            'login',
            'device.assigned',
            'device.unassigned',
            'alert.acknowledged',
            'alert.resolved',
            'ticket.created',
            'ticket.updated',
            'ticket.assigned',
        ]);

        return $query->get();
    }

    /**
     * Get temperature trends over specified days
     */
    public function getTemperatureTrends(User $user, int $days = 7): array
    {
        $deviceIds = Device::forUser($user)->pluck('id')->toArray();

        if (empty($deviceIds)) {
            return [];
        }

        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now();

        // Use TimescaleDB time_bucket for hourly aggregation
        $results = DeviceReading::whereIn('device_id', $deviceIds)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->whereNotNull('temperature')
            ->select(
                'device_id',
                DB::raw("time_bucket('1 hour', recorded_at) as time_bucket"),
                DB::raw('AVG(temperature) as avg_temperature')
            )
            ->groupBy('device_id', 'time_bucket')
            ->orderBy('time_bucket', 'asc')
            ->get();

        // Get device names
        $devices = Device::whereIn('id', array_unique($results->pluck('device_id')->toArray()))
            ->get()
            ->keyBy('id');

        // Format for charting
        $trendData = [];
        foreach ($results as $result) {
            $deviceId = $result->device_id;
            if (!isset($trendData[$deviceId])) {
                $trendData[$deviceId] = [
                    'device_id' => $deviceId,
                    'device_name' => $devices[$deviceId]->name ?? 'Unknown',
                    'data' => [],
                ];
            }

            $trendData[$deviceId]['data'][] = [
                'timestamp' => $result->time_bucket,
                'avg_temperature' => round($result->avg_temperature, 2),
            ];
        }

        return array_values($trendData);
    }

    /**
     * Get alert trends over specified days
     */
    public function getAlertTrends(User $user, int $days = 7): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now();

        $alerts = Alert::forUser($user)
            ->whereBetween('triggered_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(triggered_at) as date'),
                'severity',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'severity')
            ->orderBy('date', 'asc')
            ->get();

        // Format for charting
        $trendData = [];
        $dateRange = [];

        // Generate all dates in range
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($days - $i - 1)->format('Y-m-d');
            $dateRange[] = $date;
            $trendData[$date] = [
                'date' => $date,
                'critical' => 0,
                'warning' => 0,
                'total' => 0,
            ];
        }

        // Fill in actual data
        foreach ($alerts as $alert) {
            $date = $alert->date;
            if (isset($trendData[$date])) {
                $severityKey = strtolower($alert->severity);
                $trendData[$date][$severityKey] = $alert->count;
                $trendData[$date]['total'] += $alert->count;
            }
        }

        return array_values($trendData);
    }

    /**
     * Get top devices by alert count
     */
    public function getTopDevicesByAlerts(User $user, int $limit = 5): array
    {
        $startDate = now()->subDays(30);

        $results = Alert::forUser($user)
            ->where('triggered_at', '>=', $startDate)
            ->select(
                'device_id',
                DB::raw('COUNT(*) as alert_count')
            )
            ->groupBy('device_id')
            ->orderBy('alert_count', 'desc')
            ->limit($limit)
            ->get();

        // Get device details
        $deviceIds = $results->pluck('device_id')->toArray();
        $devices = Device::whereIn('id', $deviceIds)
            ->with(['area'])
            ->get()
            ->keyBy('id');

        return $results->map(function ($result) use ($devices) {
            $device = $devices[$result->device_id] ?? null;
            return [
                'device_id' => $result->device_id,
                'device_name' => $device?->name ?? 'Unknown',
                'area_name' => $device?->area?->name,
                'alert_count' => $result->alert_count,
            ];
        })->toArray();
    }
}

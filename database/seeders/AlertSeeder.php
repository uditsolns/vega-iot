<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all online devices with configurations
        $devices = DB::table("devices")
            ->join(
                "device_configurations",
                "devices.id",
                "=",
                "device_configurations.device_id",
            )
            ->where("devices.status", "online")
            ->where("device_configurations.is_current", true)
            ->whereNotNull("devices.area_id")
            ->select(
                "devices.id as device_id",
                "devices.type",
                "device_configurations.*",
            )
            ->get();

        if ($devices->isEmpty()) {
            $this->command->warn(
                "No online devices with configurations found. Skipping alerts seeding.",
            );
            return;
        }

        $this->command->info(
            "Seeding alerts for {$devices->count()} devices...",
        );

        $alerts = [];

        foreach ($devices as $device) {
            // Generate various types of alerts

            // 1. Active critical temperature alert (ongoing)
            $alerts[] = [
                "device_id" => $device->device_id,
                "type" => "temperature",
                "severity" => "critical",
                "status" => "active",
                "trigger_value" => 36.5,
                "threshold_breached" => $device->temp_max_critical,
                "reason" => "Temperature exceeded critical maximum threshold",
                "started_at" => now()->subHours(2),
                "ended_at" => null,
                "acknowledged_at" => null,
                "acknowledged_by" => null,
                "acknowledge_comment" => null,
                "resolved_at" => null,
                "resolved_by" => null,
                "resolve_comment" => null,
                "duration_seconds" => null,
                "is_back_in_range" => false,
                "last_notification_at" => now()->subHours(2),
                "notification_count" => 3,
                "created_at" => now()->subHours(2),
            ];

            // 2. Acknowledged warning humidity alert
            $alerts[] = [
                "device_id" => $device->device_id,
                "type" => "humidity",
                "severity" => "warning",
                "status" => "acknowledged",
                "trigger_value" => 85.0,
                "threshold_breached" => $device->humidity_max_warning,
                "reason" => "Humidity exceeded warning maximum threshold",
                "started_at" => now()->subHours(5),
                "ended_at" => null,
                "acknowledged_at" => now()->subHours(4),
                "acknowledged_by" => 1, // Assuming user ID 1 exists
                "acknowledge_comment" =>
                    "Investigating cause. HVAC team notified.",
                "resolved_at" => null,
                "resolved_by" => null,
                "resolve_comment" => null,
                "duration_seconds" => null,
                "is_back_in_range" => false,
                "last_notification_at" => now()->subHours(4),
                "notification_count" => 2,
                "created_at" => now()->subHours(5),
            ];

            // 3. Auto-resolved temperature alert (was in breach, now back to normal)
            $durationSeconds = 3600 * 3; // 3 hours
            $alerts[] = [
                "device_id" => $device->device_id,
                "type" => "temperature",
                "severity" => "warning",
                "status" => "auto_resolved",
                "trigger_value" => 31.5,
                "threshold_breached" => $device->temp_max_warning,
                "reason" => "Temperature exceeded warning maximum threshold",
                "started_at" => now()->subHours(12),
                "ended_at" => now()->subHours(9),
                "acknowledged_at" => null,
                "acknowledged_by" => null,
                "acknowledge_comment" => null,
                "resolved_at" => now()->subHours(9),
                "resolved_by" => null,
                "resolve_comment" => null,
                "duration_seconds" => $durationSeconds,
                "is_back_in_range" => true,
                "last_notification_at" => now()->subHours(12),
                "notification_count" => 1,
                "created_at" => now()->subHours(12),
            ];

            // 4. Resolved critical alert (manually resolved by user)
            $durationSeconds2 = 3600 * 2; // 2 hours
            $alerts[] = [
                "device_id" => $device->device_id,
                "type" => "temperature",
                "severity" => "critical",
                "status" => "resolved",
                "trigger_value" => 38.0,
                "threshold_breached" => $device->temp_max_critical,
                "reason" => "Temperature exceeded critical maximum threshold",
                "started_at" => now()->subDays(1),
                "ended_at" => now()->subDays(1)->addHours(2),
                "acknowledged_at" => now()->subDays(1)->addMinutes(15),
                "acknowledged_by" => 1,
                "acknowledge_comment" => "Emergency response team dispatched.",
                "resolved_at" => now()->subDays(1)->addHours(2),
                "resolved_by" => 1,
                "resolve_comment" =>
                    "Cooling system repaired. Temperature back to normal.",
                "duration_seconds" => $durationSeconds2,
                "is_back_in_range" => true,
                "last_notification_at" => now()->subDays(1)->addHours(1),
                "notification_count" => 4,
                "created_at" => now()->subDays(1),
            ];

            // 5. For dual temp devices, add temp_probe alerts
            if ($device->type === "dual_temp_humidity") {
                $alerts[] = [
                    "device_id" => $device->device_id,
                    "type" => "temp_probe",
                    "severity" => "warning",
                    "status" => "active",
                    "trigger_value" => 9.5,
                    "threshold_breached" => $device->temp_probe_max_warning,
                    "reason" =>
                        "External probe temperature exceeded warning maximum threshold",
                    "started_at" => now()->subMinutes(45),
                    "ended_at" => null,
                    "acknowledged_at" => null,
                    "acknowledged_by" => null,
                    "acknowledge_comment" => null,
                    "resolved_at" => null,
                    "resolved_by" => null,
                    "resolve_comment" => null,
                    "duration_seconds" => null,
                    "is_back_in_range" => false,
                    "last_notification_at" => now()->subMinutes(45),
                    "notification_count" => 1,
                    "created_at" => now()->subMinutes(45),
                ];
            }

            // 6. Low temperature critical alert (historical, resolved)
            $durationSeconds3 = 3600 * 4; // 4 hours
            $alerts[] = [
                "device_id" => $device->device_id,
                "type" => "temperature",
                "severity" => "critical",
                "status" => "resolved",
                "trigger_value" => -1.5,
                "threshold_breached" => $device->temp_min_critical,
                "reason" =>
                    "Temperature dropped below critical minimum threshold",
                "started_at" => now()->subDays(3),
                "ended_at" => now()->subDays(3)->addHours(4),
                "acknowledged_at" => now()->subDays(3)->addMinutes(10),
                "acknowledged_by" => 1,
                "acknowledge_comment" =>
                    "Heater malfunction detected. Maintenance called.",
                "resolved_at" => now()->subDays(3)->addHours(4),
                "resolved_by" => 1,
                "resolve_comment" => "Heater replaced. Temperature stabilized.",
                "duration_seconds" => $durationSeconds3,
                "is_back_in_range" => true,
                "last_notification_at" => now()->subDays(3)->addHours(2),
                "notification_count" => 5,
                "created_at" => now()->subDays(3),
            ];

            // Only create alerts for first few devices to avoid too much data
            if (count($alerts) >= 30) {
                break;
            }
        }

        if (!empty($alerts)) {
            DB::table("alerts")->insert($alerts);
            $this->command->info(
                "Seeded " . count($alerts) . " alerts successfully!",
            );

            // Generate some alert notifications for the alerts
            $this->seedAlertNotifications();
        }
    }

    /**
     * Seed alert notifications for created alerts
     */
    private function seedAlertNotifications(): void
    {
        $this->command->info("Seeding alert notifications...");

        // Get some alerts that have notifications and their devices
        $alerts = DB::table("alerts")
            ->join("devices", "alerts.device_id", "=", "devices.id")
            ->join("areas", "devices.area_id", "=", "areas.id")
            ->join("hubs", "areas.hub_id", "=", "hubs.id")
            ->join("locations", "hubs.location_id", "=", "locations.id")
            ->where("alerts.notification_count", ">", 0)
            ->select("alerts.*", "locations.company_id")
            ->limit(10)
            ->get();

        // Get users from the companies to send notifications to
        $usersByCompany = DB::table("users")
            ->where("is_active", true)
            ->whereNull("deleted_at")
            ->get()
            ->groupBy("company_id");

        $notifications = [];

        foreach ($alerts as $alert) {
            // Get users for this alert's company
            $companyUsers = $usersByCompany->get($alert->company_id, collect());

            if ($companyUsers->isEmpty()) {
                continue;
            }

            // Use first active user for notifications
            $user = $companyUsers->first();

            for ($i = 0; $i < $alert->notification_count; $i++) {
                $sentAt = Carbon::parse($alert->started_at)->addMinutes(
                    $i * 30,
                );

                $notifications[] = [
                    "alert_id" => $alert->id,
                    "user_id" => $user->id,
                    "channel" => "email",
                    "sent_at" => $sentAt,
                    "is_delivered" => true,
                    "delivered_at" => $sentAt->copy()->addSeconds(5),
                    "delivery_error" => null,
                    "message_content" => "[{$alert->severity}] {$alert->reason} - Device #{$alert->device_id}",
                    "external_reference" => "email_" . uniqid(),
                ];

                // Add SMS notification for critical alerts
                if ($alert->severity === "critical" && $i === 0) {
                    $notifications[] = [
                        "alert_id" => $alert->id,
                        "user_id" => $user->id,
                        "channel" => "sms",
                        "sent_at" => $sentAt,
                        "is_delivered" => true,
                        "delivered_at" => $sentAt->copy()->addSeconds(2),
                        "delivery_error" => null,
                        "message_content" => "CRITICAL: {$alert->reason}",
                        "external_reference" => "sms_" . uniqid(),
                    ];
                }
            }
        }

        if (!empty($notifications)) {
            DB::table("alert_notifications")->insert($notifications);
            $this->command->info(
                "Seeded " . count($notifications) . " alert notifications!",
            );
        }
    }
}

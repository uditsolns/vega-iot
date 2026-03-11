<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds representative alerts in every lifecycle state for ACME-DEV-0001 (Zion).
 *
 * States covered:
 *   1. ACTIVE critical      – temperature high (unacknowledged, ongoing)
 *   2. ACKNOWLEDGED warning – humidity high (seen, being monitored)
 *   3. AUTO-RESOLVED warning – temperature low (sensor recovered on its own)
 *   4. RESOLVED critical    – temperature high (manually closed 3 days ago)
 */
class AlertSeeder extends Seeder
{
    public function run(): void
    {
        $device = DB::table('devices')
            ->where('device_code', 'ACME-DEV-0001')
            ->first();

        if (!$device) {
            $this->command->warn('ACME-DEV-0001 not found. Run DeviceSeeder first.');
            return;
        }

        $tempSensor = DB::table('device_sensors')
            ->where('device_id', $device->id)
            ->where('slot_number', 1)   // temperature
            ->first();

        $humSensor = DB::table('device_sensors')
            ->where('device_id', $device->id)
            ->where('slot_number', 2)   // humidity
            ->first();

        if (!$tempSensor || !$humSensor) {
            $this->command->warn('Sensors not found for ACME-DEV-0001. Run DeviceSeeder first.');
            return;
        }

        $adminUser = DB::table('users')->where('email', 'john.smith@acme.com')->first();
        $now       = Carbon::now();

        // ── 1. ACTIVE critical — temperature high, 2 h ago ───────────────────
        DB::table('alerts')->insert([
            'device_id'            => $device->id,
            'device_sensor_id'     => $tempSensor->id,
            'severity'             => AlertSeverity::Critical->value,
            'status'               => AlertStatus::Active->value,
            'trigger_value'        => 36.40,
            'threshold_breached'   => 'max_critical',
            'reason'               => 'Value 36.4 exceeded critical maximum 35',
            'started_at'           => $now->copy()->subHours(2)->toDateTimeString(),
            'ended_at'             => null,
            'duration_seconds'     => null,
            'acknowledged_at'      => null,
            'acknowledged_by'      => null,
            'resolved_at'          => null,
            'resolved_by'          => null,
            'possible_cause'       => null,
            'root_cause'           => null,
            'corrective_action'    => null,
            'last_notification_at' => $now->copy()->subHours(2)->toDateTimeString(),
            'notification_count'   => 1,
            'created_at'           => $now->copy()->subHours(2)->toDateTimeString(),
        ]);

        // ── 2. ACKNOWLEDGED warning — humidity high, 5 h ago ─────────────────
        DB::table('alerts')->insert([
            'device_id'            => $device->id,
            'device_sensor_id'     => $humSensor->id,
            'severity'             => AlertSeverity::Warning->value,
            'status'               => AlertStatus::Acknowledged->value,
            'trigger_value'        => 82.30,
            'threshold_breached'   => 'max_warning',
            'reason'               => 'Value 82.3 exceeded warning maximum 80',
            'started_at'           => $now->copy()->subHours(5)->toDateTimeString(),
            'ended_at'             => null,
            'duration_seconds'     => null,
            'acknowledged_at'      => $now->copy()->subHours(4)->toDateTimeString(),
            'acknowledged_by'      => $adminUser?->id,
            'resolved_at'          => null,
            'resolved_by'          => null,
            'possible_cause'       => 'Environmental Factors',
            'root_cause'           => 'Door Left Open',
            'corrective_action'    => 'Preventive Maintenance',
            'last_notification_at' => $now->copy()->subHours(4)->toDateTimeString(),
            'notification_count'   => 2,
            'created_at'           => $now->copy()->subHours(5)->toDateTimeString(),
        ]);

        // ── 3. AUTO-RESOLVED warning — temperature low, yesterday ────────────
        $startedAt3  = $now->copy()->subDay()->subHours(3);
        $resolvedAt3 = $now->copy()->subDay()->subHours(1);

        DB::table('alerts')->insert([
            'device_id'            => $device->id,
            'device_sensor_id'     => $tempSensor->id,
            'severity'             => AlertSeverity::Warning->value,
            'status'               => AlertStatus::AutoResolved->value,
            'trigger_value'        => 3.80,
            'threshold_breached'   => 'min_warning',
            'reason'               => 'Value 3.8 below warning minimum 5',
            'started_at'           => $startedAt3->toDateTimeString(),
            'ended_at'             => $resolvedAt3->toDateTimeString(),
            'duration_seconds'     => $startedAt3->diffInSeconds($resolvedAt3),
            'acknowledged_at'      => null,
            'acknowledged_by'      => null,
            'resolved_at'          => null,
            'resolved_by'          => null,
            'possible_cause'       => null,
            'root_cause'           => null,
            'corrective_action'    => null,
            'last_notification_at' => $resolvedAt3->toDateTimeString(),
            'notification_count'   => 2,
            'created_at'           => $startedAt3->toDateTimeString(),
        ]);

        // ── 4. MANUALLY RESOLVED critical — temperature high, 3 days ago ─────
        $startedAt4  = $now->copy()->subDays(3);
        $resolvedAt4 = $now->copy()->subDays(3)->addHours(6);

        DB::table('alerts')->insert([
            'device_id'            => $device->id,
            'device_sensor_id'     => $tempSensor->id,
            'severity'             => AlertSeverity::Critical->value,
            'status'               => AlertStatus::Resolved->value,
            'trigger_value'        => 37.10,
            'threshold_breached'   => 'max_critical',
            'reason'               => 'Value 37.1 exceeded critical maximum 35',
            'started_at'           => $startedAt4->toDateTimeString(),
            'ended_at'             => $resolvedAt4->toDateTimeString(),
            'duration_seconds'     => $startedAt4->diffInSeconds($resolvedAt4),
            'acknowledged_at'      => $startedAt4->copy()->addMinutes(30)->toDateTimeString(),
            'acknowledged_by'      => $adminUser?->id,
            'resolved_at'          => $resolvedAt4->toDateTimeString(),
            'resolved_by'          => $adminUser?->id,
            'possible_cause'       => 'Device Failure',
            'root_cause'           => 'Air Conditioner Not Working',
            'corrective_action'    => 'Sensor/Device Replacement',
            'last_notification_at' => $resolvedAt4->toDateTimeString(),
            'notification_count'   => 4,
            'created_at'           => $startedAt4->toDateTimeString(),
        ]);

        $this->command->info('✓ Seeded 4 alerts for ACME-DEV-0001 (active, acknowledged, auto-resolved, manually resolved).');
    }
}

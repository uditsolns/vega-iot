#!/usr/bin/env php
<?php

/**
 * Alert Notification Test Script
 *
 * This script helps you quickly test the alert notification system
 * by sending a critical reading to trigger an alert.
 *
 * Usage: php test-alert-notification.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Device;
use App\Services\Reading\ReadingIngestionService;
use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "Alert Notification Test Script\n";
echo "===========================================\n\n";

// Find a test device in Acme Corporation Server Room (has email + SMS enabled)
$device = DB::table('devices')
    ->join('areas', 'devices.area_id', '=', 'areas.id')
    ->join('hubs', 'areas.hub_id', '=', 'hubs.id')
    ->join('locations', 'hubs.location_id', '=', 'locations.id')
    ->join('companies', 'locations.company_id', '=', 'companies.id')
    ->where('companies.name', 'Acme Corporation')
    ->where('areas.name', 'like', '%Server Room%')
    ->whereNotNull('devices.area_id')
    ->select('devices.*', 'areas.name as area_name', 'companies.name as company_name')
    ->first();

if (!$device) {
    echo "❌ No suitable test device found!\n";
    echo "Run: php artisan migrate:fresh --seed\n";
    exit(1);
}

$deviceModel = Device::with(['area.hub.location.company', 'currentConfiguration'])->find($device->id);

echo "✓ Found test device:\n";
echo "  - Device Code: {$deviceModel->device_code}\n";
echo "  - Device UID: {$deviceModel->device_uid}\n";
echo "  - Location: " . $deviceModel->getLocationPath() . "\n";
echo "  - Company: {$device->company_name}\n\n";

// Check configuration
$config = $deviceModel->currentConfiguration;

if (!$config) {
    echo "❌ Device has no configuration!\n";
    exit(1);
}

echo "✓ Device configuration:\n";
echo "  - Temperature Critical Max: {$config->temp_max_critical}°C\n";
echo "  - Temperature Warning Max: {$config->temp_max_warning}°C\n";
echo "  - Humidity Critical Max: {$config->humidity_max_critical}%\n";
echo "  - Humidity Warning Max: {$config->humidity_max_warning}%\n\n";

// Check area notification settings
$area = $deviceModel->area;
echo "✓ Area notification settings:\n";
echo "  - Email: " . ($area->alert_email_enabled ? 'Enabled' : 'Disabled') . "\n";
echo "  - SMS: " . ($area->alert_sms_enabled ? 'Enabled' : 'Disabled') . "\n";
echo "  - Voice: " . ($area->alert_voice_enabled ? 'Enabled' : 'Disabled') . "\n";
echo "  - Warning alerts: " . ($area->alert_warning_enabled ? 'Enabled' : 'Disabled') . "\n";
echo "  - Critical alerts: " . ($area->alert_critical_enabled ? 'Enabled' : 'Disabled') . "\n\n";

// Find test user
$testUser = DB::table('users')->where('email', 'web.tarachand@gmail.com')->first();

if (!$testUser) {
    echo "❌ Test user not found! Run: php artisan db:seed --class=UserSeeder\n";
    exit(1);
}

echo "✓ Test user found:\n";
echo "  - Name: {$testUser->first_name} {$testUser->last_name}\n";
echo "  - Email: {$testUser->email}\n";
echo "  - Phone: {$testUser->phone}\n\n";

// Prepare critical temperature reading (exceeds 35°C threshold)
$criticalReading = [
    'temperature' => 42.0, // Critical! (threshold: 35°C)
    'humidity' => 65.0,
    'temp_probe' => 5.0,
    'battery_voltage' => 3.7,
    'battery_percentage' => 85,
    'recorded_at' => now()->format('Y-m-d H:i:s'),
];

echo "Sending CRITICAL temperature reading:\n";
echo "  - Temperature: {$criticalReading['temperature']}°C (threshold: {$config->temp_max_critical}°C)\n";
echo "  - Humidity: {$criticalReading['humidity']}%\n";
echo "  - Recorded at: {$criticalReading['recorded_at']}\n\n";

echo "Are you sure you want to trigger an alert? (yes/no): ";
$confirmation = trim(fgets(STDIN));

if (strtolower($confirmation) !== 'yes') {
    echo "Test cancelled.\n";
    exit(0);
}

echo "\nIngesting reading...\n";

try {
    // Ingest the reading
    $service = app(ReadingIngestionService::class);
    $reading = $service->store($deviceModel, $criticalReading);

    echo "✓ Reading ingested successfully!\n";
    echo "  - Device ID: {$deviceModel->id}\n";
    echo "  - Recorded at: {$reading->recorded_at}\n\n";

    echo "⏳ Waiting 2 seconds for queue processing...\n";
    sleep(2);

    // Check if alert was created
    $alert = DB::table('alerts')
        ->where('device_id', $deviceModel->id)
        ->orderBy('id', 'desc')
        ->first();

    if ($alert) {
        echo "\n✓ Alert created:\n";
        echo "  - Alert ID: {$alert->id}\n";
        echo "  - Type: {$alert->type}\n";
        echo "  - Severity: {$alert->severity}\n";
        echo "  - Status: {$alert->status}\n";
        echo "  - Reason: {$alert->reason}\n";
        echo "  - Trigger Value: {$alert->trigger_value}\n\n";

        // Check notifications
        $notifications = DB::table('alert_notifications')
            ->where('alert_id', $alert->id)
            ->get();

        if ($notifications->count() > 0) {
            echo "✓ Notifications created:\n";
            foreach ($notifications as $notif) {
                $statusIcon = match($notif->status) {
                    'sent' => '✓',
                    'pending' => '⏳',
                    'failed' => '❌',
                    default => '?'
                };
                echo "  {$statusIcon} Channel: {$notif->channel}, Status: {$notif->status}";
                if ($notif->sent_at) {
                    echo ", Sent at: {$notif->sent_at}";
                }
                if ($notif->error_message) {
                    echo ", Error: {$notif->error_message}";
                }
                echo "\n";
            }
        } else {
            echo "⚠ No notifications created yet. Check if queue workers are running.\n";
        }
    } else {
        echo "\n⚠ Alert not created yet. Check if queue workers are running.\n";
    }

    echo "\n===========================================\n";
    echo "Test completed!\n";
    echo "===========================================\n\n";

    echo "Next steps:\n";
    echo "1. Check email at: {$testUser->email}\n";
    echo "2. Check SMS on phone: {$testUser->phone}\n";
    echo "3. Monitor queue workers in terminals\n";
    echo "4. Check logs: php artisan pail\n\n";

    echo "To send a normal reading and auto-resolve the alert, run:\n";
    echo "php test-alert-notification.php --resolve\n\n";

    echo "To view the alert in database:\n";
    echo "php artisan tinker\n";
    echo ">>> DB::table('alerts')->find({$alert->id});\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

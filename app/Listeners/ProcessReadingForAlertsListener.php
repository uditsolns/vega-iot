<?php

namespace App\Listeners;

use App\Events\ReadingReceived;
use App\Models\Device;
use App\Services\Alert\AlertLifecycleService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessReadingForAlertsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = "alerts";
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly AlertLifecycleService $alertLifecycleService,
    ) {}

    public function handle(ReadingReceived $event): void
    {
        try {
            $device = Device::with([
                'sensors.sensorType',
                'sensors.currentConfiguration',
                'area.hub.location'
            ])->find($event->deviceId);

            if (!$device) {
                Log::warning('Device not found for alert processing', [
                    'device_id' => $event->deviceId,
                ]);
                return;
            }

            // Process each sensor reading
            foreach ($event->sensorReadings as $reading) {
                $sensor = $device->sensors->firstWhere('id', $reading['sensor_id']);

                if (!$sensor || !$sensor->is_enabled) {
                    continue;
                }

                // Skip sensors that don't support threshold configuration
                if (!$sensor->sensorType->supports_threshold_config) {
                    continue;
                }

                // Evaluate this sensor's reading
                $this->alertLifecycleService->evaluateSensor(
                    $sensor,
                    $reading['value'],
                    $event->recordedAt
                );
            }
        } catch (Exception $e) {
            Log::error('Alert processing listener failed', [
                'device_id' => $event->deviceId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    public function failed(ReadingReceived $event, \Throwable $exception): void
    {
        Log::error('Alert processing listener permanently failed', [
            'device_id' => $event->deviceId,
            'error'     => $exception->getMessage(),
        ]);
    }
}

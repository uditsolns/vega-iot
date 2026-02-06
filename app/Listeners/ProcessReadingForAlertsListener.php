<?php

namespace App\Listeners;

use App\Events\ReadingReceived;
use App\Models\Device;
use App\Models\DeviceReading;
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

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly AlertLifecycleService $alertLifecycleService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ReadingReceived $event): void
    {
        try {
            // Load the device fresh from database
            $device = Device::with(['currentConfiguration', 'area.hub.location'])
                ->find($event->deviceId);

            if (!$device) {
                Log::warning('Device not found for alert processing', [
                    'device_id' => $event->deviceId,
                ]);
                return;
            }

            // Find the DeviceReading record
            $reading = DeviceReading::where('device_id', $event->deviceId)
                ->where('recorded_at', $event->recordedAt)
                ->first();

            if (!$reading) {
                Log::warning('DeviceReading not found for alert processing', [
                    'device_id' => $event->deviceId,
                    'recorded_at' => $event->recordedAt,
                ]);
                return;
            }

            // Evaluate and process alerts
            $this->alertLifecycleService->evaluateAndProcess($device, $reading);
        } catch (Exception $e) {
            Log::error('Alert processing listener failed', [
                'device_id' => $event->deviceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ReadingReceived $event, \Throwable $exception): void
    {
        Log::error('Alert processing listener permanently failed', [
            'device_id' => $event->deviceId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckOfflineDevices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $thresholdMinutes = 15) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $threshold = now()->subMinutes($this->thresholdMinutes);

        // Get all online devices that haven't reported in threshold time
        $devices = Device::where("status", DeviceStatus::Online)
            ->where(function ($query) use ($threshold) {
                $query
                    ->where("last_reading_at", "<", $threshold)
                    ->orWhereNull("last_reading_at");
            })
            ->get();

        $offlineCount = 0;

        foreach ($devices as $device) {
            // Update device status to offline
            $device->update(["status" => DeviceStatus::Offline]);

            // Dispatch event for each device that went offline
            // event(new DeviceWentOffline($device));

            // TODO: Create device status alert if configured
            // if ($device->area && $device->area->alert_config) {
            //     $this->createDeviceStatusAlert($device);
            // }

            $offlineCount++;
        }

        if ($offlineCount > 0) {
            Log::info("Checked offline devices", [
                "threshold_minutes" => $this->thresholdMinutes,
                "devices_marked_offline" => $offlineCount,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("CheckOfflineDevices job failed", [
            "threshold_minutes" => $this->thresholdMinutes,
            "error" => $exception->getMessage(),
        ]);
    }

    /**
     * Create device status alert
     */
    private function createDeviceStatusAlert(Device $device): void
    {
        // TODO: Implement device status alert creation
        // This would integrate with the Alert system to create an alert
        // when a device goes offline
    }
}

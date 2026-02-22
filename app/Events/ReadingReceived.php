<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $deviceId,
        public readonly string $recordedAt,
        public readonly array $sensorReadings // [['sensor_id' => 1, 'value' => 23.5], ...]
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("devices.{$this->deviceId}");
    }

    public function broadcastAs(): string
    {
        return 'reading.received';
    }

    public function broadcastWith(): array
    {
        return [
            'device_id'       => $this->deviceId,
            'recorded_at'     => $this->recordedAt,
            'sensor_readings' => $this->sensorReadings,
        ];
    }
}

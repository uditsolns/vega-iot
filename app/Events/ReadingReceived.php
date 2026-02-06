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

    /**
     * Store only IDs and primitive data, not the entire Device model
     */
    public function __construct(
        public readonly int $deviceId,
        public readonly string $recordedAt,
        public readonly array $readingData
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel("devices.{$this->deviceId}");
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'reading.received';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'device_id' => $this->deviceId,
            'recorded_at' => $this->recordedAt,
            'temperature' => $this->readingData['temperature'] ?? null,
            'humidity' => $this->readingData['humidity'] ?? null,
            'temp_probe' => $this->readingData['temp_probe'] ?? null,
            'battery_percentage' => $this->readingData['battery_percentage'] ?? null,
        ];
    }
}

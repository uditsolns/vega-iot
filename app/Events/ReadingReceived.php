<?php

namespace App\Events;

use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReadingReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The device that sent the reading.
     *
     * @var Device
     */
    public Device $device;

    /**
     * The reading data.
     *
     * @var array
     */
    public array $reading;

    /**
     * Create a new event instance.
     */
    public function __construct(Device $device, array $reading)
    {
        $this->device = $device;
        $this->reading = $reading;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel("devices." . $this->device->id);
    }

}

<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlightStatisticsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public array $statistics
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard'),
        ];
    }

    /**
     * Get the event name for broadcasting
     */
    public function broadcastAs(): string
    {
        return 'statistics.updated';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'statistics' => $this->statistics,
            'timestamp' => now()->toISOString(),
        ];
    }
}

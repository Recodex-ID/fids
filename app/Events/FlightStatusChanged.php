<?php

namespace App\Events;

use App\Models\Flight;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FlightStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Flight $flight,
        public string $oldStatus,
        public string $newStatus
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('flights'),
            new Channel("flight.{$this->flight->id}"),
            new Channel("airport.{$this->flight->origin_airport_id}"),
            new Channel("airport.{$this->flight->destination_airport_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'flight.status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'flight' => [
                'id' => $this->flight->id,
                'flight_number' => $this->flight->flight_number,
                'status' => $this->newStatus,
                'old_status' => $this->oldStatus,
                'airline' => $this->flight->airline->only(['id', 'name', 'code']),
                'origin_airport' => $this->flight->originAirport->only(['id', 'name', 'code', 'city']),
                'destination_airport' => $this->flight->destinationAirport->only(['id', 'name', 'code', 'city']),
                'scheduled_departure' => $this->flight->scheduled_departure?->format('Y-m-d H:i:s'),
                'scheduled_arrival' => $this->flight->scheduled_arrival?->format('Y-m-d H:i:s'),
                'gate' => $this->flight->gate,
                'aircraft_type' => $this->flight->aircraft_type,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}

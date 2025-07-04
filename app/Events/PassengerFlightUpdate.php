<?php

namespace App\Events;

use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PassengerFlightUpdate implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Passenger $passenger,
        public Flight $flight,
        public string $updateType,
        public string $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("passenger.{$this->passenger->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'passenger.flight.update';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'passenger' => [
                'id' => $this->passenger->id,
                'name' => $this->passenger->name,
                'booking_reference' => $this->passenger->booking_reference,
                'seat_number' => $this->passenger->seat_number,
            ],
            'flight' => [
                'id' => $this->flight->id,
                'flight_number' => $this->flight->flight_number,
                'status' => $this->flight->status,
                'airline' => $this->flight->airline->only(['id', 'name', 'code']),
                'origin_airport' => $this->flight->originAirport->only(['id', 'name', 'code', 'city']),
                'destination_airport' => $this->flight->destinationAirport->only(['id', 'name', 'code', 'city']),
                'scheduled_departure' => $this->flight->scheduled_departure?->format('Y-m-d H:i:s'),
                'scheduled_arrival' => $this->flight->scheduled_arrival?->format('Y-m-d H:i:s'),
                'gate' => $this->flight->gate,
            ],
            'update_type' => $this->updateType,
            'message' => $this->message,
            'timestamp' => now()->toISOString(),
        ];
    }
}

<?php

namespace App\Jobs;

use App\Events\PassengerFlightUpdate;
use App\Models\Flight;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPassengerNotifications implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Flight $flight,
        public string $message,
        public string $type = 'flight_update'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all passengers for this flight
        $passengers = $this->flight->passengers()->get();

        foreach ($passengers as $passenger) {
            // Create notification record
            $notification = Notification::create([
                'passenger_id' => $passenger->id,
                'flight_id' => $this->flight->id,
                'type' => $this->type,
                'message' => $this->message,
                'sent_at' => now(),
            ]);

            // Broadcast real-time notification to passenger
            broadcast(new PassengerFlightUpdate(
                $passenger,
                $this->flight,
                $this->type,
                $this->message
            ));

            // You can add email/SMS notifications here
            // Mail::to($passenger->email)->queue(new FlightUpdateMail($passenger, $this->flight, $this->message));
        }
    }
}

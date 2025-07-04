<?php

namespace App\Jobs;

use App\Events\FlightStatusChanged;
use App\Models\Flight;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFlightStatusUpdate implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Flight $flight,
        public string $newStatus,
        public string $oldStatus
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update flight status
        $this->flight->update(['status' => $this->newStatus]);

        // Broadcast the status change
        broadcast(new FlightStatusChanged($this->flight, $this->oldStatus, $this->newStatus));

        // Dispatch passenger notifications if needed
        if ($this->shouldNotifyPassengers()) {
            SendPassengerNotifications::dispatch($this->flight, $this->getNotificationMessage());
        }

        // Update flight statistics for both airports and globally
        UpdateFlightStatistics::dispatch($this->flight->origin_airport_id);
        UpdateFlightStatistics::dispatch($this->flight->destination_airport_id);
        UpdateFlightStatistics::dispatch(); // Global statistics
        
        // Log status change for monitoring
        \Log::info('Flight status updated', [
            'flight_id' => $this->flight->id,
            'flight_number' => $this->flight->flight_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'origin_airport' => $this->flight->origin_airport_id,
            'destination_airport' => $this->flight->destination_airport_id,
            'timestamp' => now(),
        ]);
    }

    /**
     * Determine if passengers should be notified for this status change.
     */
    private function shouldNotifyPassengers(): bool
    {
        return in_array($this->newStatus, ['delayed', 'boarding', 'cancelled', 'departed']);
    }

    /**
     * Get the notification message for passengers.
     */
    private function getNotificationMessage(): string
    {
        return match ($this->newStatus) {
            'delayed' => "Your flight {$this->flight->flight_number} has been delayed.",
            'boarding' => "Your flight {$this->flight->flight_number} is now boarding at gate {$this->flight->gate}.",
            'cancelled' => "Your flight {$this->flight->flight_number} has been cancelled. Please contact customer service.",
            'departed' => "Your flight {$this->flight->flight_number} has departed.",
            default => "Your flight {$this->flight->flight_number} status has been updated to {$this->newStatus}.",
        };
    }
}

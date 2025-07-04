<?php

namespace App\Observers;

use App\Events\FlightBoarding;
use App\Events\FlightDelayed;
use App\Events\FlightGateChanged;
use App\Events\FlightStatusChanged;
use App\Jobs\ProcessFlightStatusUpdate;
use App\Models\Flight;

class FlightObserver
{
    /**
     * Handle the Flight "created" event.
     */
    public function created(Flight $flight): void
    {
        // Broadcast new flight creation
        broadcast(new FlightStatusChanged($flight, '', $flight->status));
    }

    /**
     * Handle the Flight "updated" event.
     */
    public function updated(Flight $flight): void
    {
        // Check if status changed
        if ($flight->isDirty('status')) {
            $oldStatus = $flight->getOriginal('status');
            $newStatus = $flight->status;

            // Dispatch background job for complex status processing
            ProcessFlightStatusUpdate::dispatch($flight, $newStatus, $oldStatus);

            // Handle specific status changes
            if ($newStatus === 'boarding') {
                broadcast(new FlightBoarding($flight));
            }

            if ($newStatus === 'delayed') {
                // Calculate delay if possible
                $scheduledDeparture = $flight->scheduled_departure;
                $actualDeparture = $flight->actual_departure;
                $delayMinutes = 0;

                if ($scheduledDeparture && $actualDeparture) {
                    $delayMinutes = $scheduledDeparture->diffInMinutes($actualDeparture);
                }

                broadcast(new FlightDelayed($flight, $delayMinutes, 'Flight delayed due to operational reasons'));
            }
        }

        // Check if gate changed
        if ($flight->isDirty('gate')) {
            $oldGate = $flight->getOriginal('gate');
            $newGate = $flight->gate;

            broadcast(new FlightGateChanged($flight, $oldGate, $newGate));

            // Notify passengers about gate change
            if ($flight->passengers()->count() > 0) {
                \App\Jobs\SendPassengerNotifications::dispatch(
                    $flight,
                    "Gate change: Your flight {$flight->flight_number} has moved to gate {$newGate}",
                    'gate_change'
                );
            }
        }
    }

    /**
     * Handle the Flight "deleted" event.
     */
    public function deleted(Flight $flight): void
    {
        // Broadcast flight cancellation
        broadcast(new FlightStatusChanged($flight, $flight->status, 'cancelled'));
    }

    /**
     * Handle the Flight "restored" event.
     */
    public function restored(Flight $flight): void
    {
        // Broadcast flight restoration
        broadcast(new FlightStatusChanged($flight, 'cancelled', $flight->status));
    }

    /**
     * Handle the Flight "force deleted" event.
     */
    public function forceDeleted(Flight $flight): void
    {
        // Final cleanup if needed
    }
}

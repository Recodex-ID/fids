<?php

namespace App\Jobs;

use App\Models\Flight;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateFlightStatistics implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $airportId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update global flight statistics
        $this->updateGlobalStatistics();

        // Update airport-specific statistics if provided
        if ($this->airportId) {
            $this->updateAirportStatistics($this->airportId);
        }

        // Broadcast updated statistics to dashboard
        $this->broadcastStatistics();
    }

    /**
     * Update global flight statistics
     */
    private function updateGlobalStatistics(): void
    {
        $stats = [
            'total_flights' => Flight::count(),
            'on_time' => Flight::where('status', 'on_time')->count(),
            'delayed' => Flight::where('status', 'delayed')->count(),
            'boarding' => Flight::where('status', 'boarding')->count(),
            'departed' => Flight::where('status', 'departed')->count(),
            'arrived' => Flight::where('status', 'arrived')->count(),
            'cancelled' => Flight::where('status', 'cancelled')->count(),
            'avg_delay_minutes' => $this->calculateAverageDelay(),
            'last_updated' => now(),
        ];

        Cache::put('flight_statistics', $stats, now()->addMinutes(5));
    }

    /**
     * Update airport-specific statistics
     */
    private function updateAirportStatistics(int $airportId): void
    {
        $departureStats = [
            'total_departures' => Flight::where('origin_airport_id', $airportId)->count(),
            'on_time_departures' => Flight::where('origin_airport_id', $airportId)
                ->where('status', 'on_time')->count(),
            'delayed_departures' => Flight::where('origin_airport_id', $airportId)
                ->where('status', 'delayed')->count(),
        ];

        $arrivalStats = [
            'total_arrivals' => Flight::where('destination_airport_id', $airportId)->count(),
            'on_time_arrivals' => Flight::where('destination_airport_id', $airportId)
                ->where('status', 'arrived')->count(),
            'delayed_arrivals' => Flight::where('destination_airport_id', $airportId)
                ->where('status', 'delayed')->count(),
        ];

        Cache::put("airport_{$airportId}_departures", $departureStats, now()->addMinutes(5));
        Cache::put("airport_{$airportId}_arrivals", $arrivalStats, now()->addMinutes(5));
    }

    /**
     * Calculate average delay in minutes
     */
    private function calculateAverageDelay(): float
    {
        return Flight::where('status', 'delayed')
            ->whereNotNull('actual_departure')
            ->whereNotNull('scheduled_departure')
            ->get()
            ->avg(function ($flight) {
                return $flight->scheduled_departure->diffInMinutes($flight->actual_departure);
            }) ?? 0;
    }

    /**
     * Broadcast statistics to dashboard
     */
    private function broadcastStatistics(): void
    {
        $stats = Cache::get('flight_statistics');
        
        if ($stats) {
            broadcast(new \App\Events\FlightStatisticsUpdated($stats));
        }
    }
}

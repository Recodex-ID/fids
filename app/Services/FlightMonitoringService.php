<?php

namespace App\Services;

use App\Models\Flight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FlightMonitoringService
{
    /**
     * Monitor flight performance and log anomalies
     */
    public function monitorFlightPerformance(): void
    {
        $this->checkDelayedFlights();
        $this->checkMissingGates();
        $this->checkAirportCapacity();
        $this->logSystemHealth();
    }

    /**
     * Check for significantly delayed flights
     */
    private function checkDelayedFlights(): void
    {
        $criticallyDelayed = Flight::where('status', 'delayed')
            ->whereNotNull('actual_departure')
            ->whereNotNull('scheduled_departure')
            ->get()
            ->filter(function ($flight) {
                $delayMinutes = $flight->scheduled_departure->diffInMinutes($flight->actual_departure);
                return $delayMinutes > 120; // More than 2 hours
            });

        if ($criticallyDelayed->count() > 0) {
            Log::warning('Critically delayed flights detected', [
                'count' => $criticallyDelayed->count(),
                'flights' => $criticallyDelayed->pluck('flight_number')->toArray(),
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Check for flights without assigned gates
     */
    private function checkMissingGates(): void
    {
        $flightsWithoutGates = Flight::whereIn('status', ['boarding', 'delayed'])
            ->whereNull('gate')
            ->count();

        if ($flightsWithoutGates > 0) {
            Log::warning('Flights without assigned gates', [
                'count' => $flightsWithoutGates,
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Check airport capacity and congestion
     */
    private function checkAirportCapacity(): void
    {
        $congestionThreshold = 20; // flights per hour
        
        $airportCongestion = Flight::whereIn('status', ['boarding', 'delayed', 'on_time'])
            ->whereBetween('scheduled_departure', [now(), now()->addHour()])
            ->selectRaw('origin_airport_id, COUNT(*) as departure_count')
            ->groupBy('origin_airport_id')
            ->having('departure_count', '>', $congestionThreshold)
            ->get();

        foreach ($airportCongestion as $congestion) {
            Log::warning('Airport departure congestion detected', [
                'airport_id' => $congestion->origin_airport_id,
                'departure_count' => $congestion->departure_count,
                'threshold' => $congestionThreshold,
                'timestamp' => now(),
            ]);
        }
    }

    /**
     * Log system health metrics
     */
    private function logSystemHealth(): void
    {
        $stats = [
            'total_active_flights' => Flight::whereIn('status', ['boarding', 'delayed', 'on_time', 'departed'])->count(),
            'total_delayed_flights' => Flight::where('status', 'delayed')->count(),
            'total_cancelled_flights' => Flight::where('status', 'cancelled')->count(),
            'system_load' => sys_getloadavg()[0] ?? 0,
            'memory_usage' => memory_get_usage(true),
            'timestamp' => now(),
        ];

        // Cache for dashboard display
        Cache::put('system_health', $stats, now()->addMinutes(5));

        Log::info('System health check', $stats);
    }

    /**
     * Get real-time performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $totalFlights = Flight::count();
        
        if ($totalFlights === 0) {
            return [
                'on_time_percentage' => 0,
                'delay_percentage' => 0,
                'cancellation_percentage' => 0,
                'average_delay_minutes' => 0,
            ];
        }

        $onTime = Flight::where('status', 'on_time')->count();
        $delayed = Flight::where('status', 'delayed')->count();
        $cancelled = Flight::where('status', 'cancelled')->count();

        $averageDelay = Flight::where('status', 'delayed')
            ->whereNotNull('actual_departure')
            ->whereNotNull('scheduled_departure')
            ->get()
            ->avg(function ($flight) {
                return $flight->scheduled_departure->diffInMinutes($flight->actual_departure);
            }) ?? 0;

        return [
            'on_time_percentage' => round(($onTime / $totalFlights) * 100, 2),
            'delay_percentage' => round(($delayed / $totalFlights) * 100, 2),
            'cancellation_percentage' => round(($cancelled / $totalFlights) * 100, 2),
            'average_delay_minutes' => round($averageDelay, 2),
            'total_flights' => $totalFlights,
            'timestamp' => now(),
        ];
    }

    /**
     * Log broadcast event for monitoring
     */
    public function logBroadcastEvent(string $eventType, array $data): void
    {
        Log::info('Broadcast event sent', [
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }
}
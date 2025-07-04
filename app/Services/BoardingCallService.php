<?php

namespace App\Services;

use App\Models\Flight;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BoardingCallService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Process automated boarding calls for eligible flights
     */
    public function processAutomatedBoardingCalls(): void
    {
        $eligibleFlights = $this->getEligibleFlights();
        
        foreach ($eligibleFlights as $flight) {
            $this->processBoardingCallsForFlight($flight);
        }
        
        Log::info('Automated boarding call processing completed', [
            'processed_flights' => $eligibleFlights->count(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Get flights eligible for boarding call processing
     */
    protected function getEligibleFlights(): Collection
    {
        return Flight::whereIn('status', ['on_time', 'delayed'])
            ->whereNotNull('gate')
            ->whereNotNull('scheduled_departure')
            ->where('scheduled_departure', '>=', now())
            ->where('scheduled_departure', '<=', now()->addHours(2))
            ->with(['passengers.notificationPreferences'])
            ->get()
            ->filter(function ($flight) {
                // Only process flights that haven't had boarding calls sent recently
                $cacheKey = "boarding_calls_sent_{$flight->id}";
                return !Cache::has($cacheKey);
            });
    }

    /**
     * Process boarding calls for a specific flight
     */
    protected function processBoardingCallsForFlight(Flight $flight): void
    {
        $now = now();
        $scheduledDeparture = $flight->scheduled_departure;
        
        // Get passenger preferences for boarding call timing
        $passengers = $flight->passengers()
            ->with('notificationPreferences')
            ->get();
            
        // Group passengers by their boarding call preferences
        $passengerGroups = $this->groupPassengersByBoardingPreferences($passengers);
        
        foreach ($passengerGroups as $advanceMinutes => $groupPassengers) {
            $boardingTime = $scheduledDeparture->copy()->subMinutes($advanceMinutes);
            
            // Check if it's time to send boarding calls for this group
            if ($now->greaterThanOrEqualTo($boardingTime) && $now->lessThan($boardingTime->copy()->addMinutes(5))) {
                $this->sendBoardingCallsToGroup($flight, $groupPassengers, $advanceMinutes);
            }
        }
        
        // Send final boarding call 10 minutes before departure
        $finalBoardingTime = $scheduledDeparture->copy()->subMinutes(10);
        if ($now->greaterThanOrEqualTo($finalBoardingTime) && $now->lessThan($finalBoardingTime->copy()->addMinutes(5))) {
            $this->sendFinalBoardingCall($flight);
        }
        
        // Send last call 5 minutes before departure
        $lastCallTime = $scheduledDeparture->copy()->subMinutes(5);
        if ($now->greaterThanOrEqualTo($lastCallTime) && $now->lessThan($lastCallTime->copy()->addMinutes(5))) {
            $this->sendLastBoardingCall($flight);
        }
    }

    /**
     * Group passengers by their boarding call advance preferences
     */
    protected function groupPassengersByBoardingPreferences(Collection $passengers): array
    {
        $groups = [];
        
        foreach ($passengers as $passenger) {
            $preferences = $passenger->notificationPreferences;
            $advanceMinutes = $preferences?->boarding_call_advance_minutes ?? 30;
            
            if (!isset($groups[$advanceMinutes])) {
                $groups[$advanceMinutes] = collect();
            }
            
            $groups[$advanceMinutes]->push($passenger);
        }
        
        return $groups;
    }

    /**
     * Send boarding calls to a group of passengers
     */
    protected function sendBoardingCallsToGroup(Flight $flight, Collection $passengers, int $advanceMinutes): void
    {
        // Check if we've already sent boarding calls for this advance time
        $cacheKey = "boarding_calls_{$flight->id}_{$advanceMinutes}";
        if (Cache::has($cacheKey)) {
            return;
        }
        
        // Update flight status to boarding if not already
        if ($flight->status !== 'boarding') {
            $flight->update(['status' => 'boarding']);
        }
        
        // Send boarding notifications
        $this->notificationService->sendBoardingNotification($flight);
        
        // Cache to prevent duplicate sends
        Cache::put($cacheKey, true, now()->addHours(1));
        
        Log::info('Boarding calls sent to passenger group', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'advance_minutes' => $advanceMinutes,
            'passenger_count' => $passengers->count(),
            'gate' => $flight->gate,
        ]);
    }

    /**
     * Send final boarding call to all passengers
     */
    protected function sendFinalBoardingCall(Flight $flight): void
    {
        $cacheKey = "final_boarding_call_{$flight->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        
        // Send high priority boarding notification
        $this->notificationService->sendBoardingNotification($flight);
        
        // Cache to prevent duplicates
        Cache::put($cacheKey, true, now()->addHours(1));
        
        Log::info('Final boarding call sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'gate' => $flight->gate,
            'departure_time' => $flight->scheduled_departure,
        ]);
    }

    /**
     * Send last boarding call (gate closing soon)
     */
    protected function sendLastBoardingCall(Flight $flight): void
    {
        $cacheKey = "last_boarding_call_{$flight->id}";
        if (Cache::has($cacheKey)) {
            return;
        }
        
        // Send urgent priority boarding notification
        $this->notificationService->sendBoardingNotification($flight);
        
        // Cache to prevent duplicates
        Cache::put($cacheKey, true, now()->addHours(1));
        
        Log::warning('Last boarding call sent - gate closing soon', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'gate' => $flight->gate,
            'departure_time' => $flight->scheduled_departure,
        ]);
    }

    /**
     * Send boarding call for specific passenger (manual trigger)
     */
    public function sendManualBoardingCall(Flight $flight, int $passengerId): bool
    {
        $passenger = $flight->passengers()->find($passengerId);
        
        if (!$passenger) {
            return false;
        }
        
        // Check if passenger preferences allow boarding calls
        $preferences = $passenger->notificationPreferences;
        if ($preferences && !$preferences->isNotificationTypeEnabled('boarding_call')) {
            return false;
        }
        
        // Send boarding notification to specific passenger
        $passenger->notify(new \App\Notifications\FlightBoardingNotification($flight));
        
        Log::info('Manual boarding call sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'passenger_id' => $passengerId,
            'passenger_name' => $passenger->name,
        ]);
        
        return true;
    }

    /**
     * Get boarding call statistics
     */
    public function getBoardingCallStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        // Count boarding notifications sent
        $boardingNotifications = \App\Models\Notification::where('type', 'boarding_call')
            ->where('created_at', '>=', $startDate)
            ->get();
            
        return [
            'total_boarding_calls' => $boardingNotifications->count(),
            'successful_deliveries' => $boardingNotifications->where('status', 'delivered')->count(),
            'failed_deliveries' => $boardingNotifications->where('status', 'failed')->count(),
            'unique_flights' => $boardingNotifications->pluck('flight_id')->unique()->count(),
            'by_channel' => $boardingNotifications->flatMap(function ($notification) {
                return $notification->delivery_channels ?? [];
            })->countBy()->toArray(),
            'average_advance_time' => $this->calculateAverageAdvanceTime(),
        ];
    }

    /**
     * Calculate average boarding call advance time
     */
    protected function calculateAverageAdvanceTime(): float
    {
        $preferences = \App\Models\NotificationPreference::where('boarding_calls', true)
            ->get();
            
        if ($preferences->isEmpty()) {
            return 30; // Default
        }
        
        return $preferences->avg('boarding_call_advance_minutes');
    }

    /**
     * Clean up boarding call cache entries for completed flights
     */
    public function cleanupBoardingCallCache(): int
    {
        $completedFlights = Flight::whereIn('status', ['departed', 'arrived', 'cancelled'])
            ->where('scheduled_departure', '<', now()->subHours(2))
            ->pluck('id');
            
        $cleaned = 0;
        foreach ($completedFlights as $flightId) {
            // Clean up various cache keys for this flight
            $cacheKeys = [
                "boarding_calls_sent_{$flightId}",
                "final_boarding_call_{$flightId}",
                "last_boarding_call_{$flightId}",
            ];
            
            // Also clean advance-specific cache keys
            for ($minutes = 10; $minutes <= 120; $minutes += 5) {
                $cacheKeys[] = "boarding_calls_{$flightId}_{$minutes}";
            }
            
            foreach ($cacheKeys as $key) {
                if (Cache::forget($key)) {
                    $cleaned++;
                }
            }
        }
        
        Log::info('Boarding call cache cleanup completed', [
            'cleaned_entries' => $cleaned,
            'processed_flights' => $completedFlights->count(),
        ]);
        
        return $cleaned;
    }
}
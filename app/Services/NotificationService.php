<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Passenger;
use App\Notifications\FlightBoardingNotification;
use App\Notifications\FlightCancellationNotification;
use App\Notifications\FlightDelayNotification;
use App\Notifications\FlightGateChangeNotification;
use App\Notifications\FlightStatusChangeNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send flight status change notifications
     */
    public function sendFlightStatusChangeNotification(
        Flight $flight, 
        string $oldStatus, 
        string $newStatus
    ): void {
        $passengers = $this->getEligiblePassengers($flight, 'flight_status_change');
        
        if ($passengers->isEmpty()) {
            return;
        }

        $notification = new FlightStatusChangeNotification($flight, $oldStatus, $newStatus);
        
        $this->dispatchNotifications($passengers, $notification);
        
        Log::info('Flight status change notifications sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'passenger_count' => $passengers->count(),
        ]);
    }

    /**
     * Send gate change notifications
     */
    public function sendGateChangeNotification(
        Flight $flight, 
        string $oldGate = null, 
        string $newGate = null
    ): void {
        $passengers = $this->getEligiblePassengers($flight, 'gate_change');
        
        if ($passengers->isEmpty()) {
            return;
        }

        $notification = new FlightGateChangeNotification($flight, $oldGate, $newGate);
        
        $this->dispatchNotifications($passengers, $notification);
        
        Log::info('Gate change notifications sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'old_gate' => $oldGate,
            'new_gate' => $newGate,
            'passenger_count' => $passengers->count(),
        ]);
    }

    /**
     * Send boarding call notifications
     */
    public function sendBoardingNotification(Flight $flight): void
    {
        $passengers = $this->getEligiblePassengers($flight, 'boarding_call');
        
        if ($passengers->isEmpty()) {
            return;
        }

        $notification = new FlightBoardingNotification($flight);
        
        $this->dispatchNotifications($passengers, $notification);
        
        Log::info('Boarding notifications sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'gate' => $flight->gate,
            'passenger_count' => $passengers->count(),
        ]);
    }

    /**
     * Send delay notifications
     */
    public function sendDelayNotification(
        Flight $flight, 
        int $delayMinutes = 0, 
        string $reason = null
    ): void {
        $passengers = $this->getEligiblePassengers($flight, 'delay');
        
        // Filter passengers based on delay threshold preference
        $eligiblePassengers = $passengers->filter(function ($passenger) use ($delayMinutes) {
            $preferences = $passenger->notificationPreferences;
            if (!$preferences) {
                return $delayMinutes >= 15; // Default threshold
            }
            
            return $delayMinutes >= $preferences->delay_notification_threshold;
        });
        
        if ($eligiblePassengers->isEmpty()) {
            return;
        }

        $notification = new FlightDelayNotification($flight, $delayMinutes, $reason);
        
        $this->dispatchNotifications($eligiblePassengers, $notification);
        
        Log::info('Delay notifications sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'delay_minutes' => $delayMinutes,
            'reason' => $reason,
            'passenger_count' => $eligiblePassengers->count(),
        ]);
    }

    /**
     * Send cancellation notifications
     */
    public function sendCancellationNotification(Flight $flight, string $reason = null): void
    {
        $passengers = $this->getEligiblePassengers($flight, 'cancellation');
        
        if ($passengers->isEmpty()) {
            return;
        }

        $notification = new FlightCancellationNotification($flight, $reason);
        
        $this->dispatchNotifications($passengers, $notification);
        
        Log::info('Cancellation notifications sent', [
            'flight_id' => $flight->id,
            'flight_number' => $flight->flight_number,
            'reason' => $reason,
            'passenger_count' => $passengers->count(),
        ]);
    }

    /**
     * Send check-in reminders
     */
    public function sendCheckInReminders(): void
    {
        // Get flights departing in 24 hours that haven't had check-in reminders sent
        $flights = Flight::whereDate('scheduled_departure', now()->addDay())
            ->whereTime('scheduled_departure', '>=', now()->addDay()->startOfDay())
            ->whereTime('scheduled_departure', '<=', now()->addDay()->endOfDay())
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', 'check_in_reminder')
                      ->where('created_at', '>=', now()->subHours(6));
            })
            ->with(['passengers.notificationPreferences'])
            ->get();

        foreach ($flights as $flight) {
            $passengers = $this->getEligiblePassengers($flight, 'check_in_reminder');
            
            if ($passengers->isEmpty()) {
                continue;
            }

            // Create a simple check-in reminder notification
            // You could create a dedicated CheckInReminderNotification class
            $notification = new FlightStatusChangeNotification($flight, 'scheduled', 'check_in_reminder');
            
            $this->dispatchNotifications($passengers, $notification);
            
            Log::info('Check-in reminders sent', [
                'flight_id' => $flight->id,
                'flight_number' => $flight->flight_number,
                'passenger_count' => $passengers->count(),
            ]);
        }
    }

    /**
     * Get passengers eligible for notification type
     */
    protected function getEligiblePassengers(Flight $flight, string $notificationType): Collection
    {
        return $flight->passengers()
            ->with('notificationPreferences')
            ->get()
            ->filter(function ($passenger) use ($notificationType) {
                $preferences = $passenger->notificationPreferences;
                
                // If no preferences, use defaults (most notifications enabled)
                if (!$preferences) {
                    return in_array($notificationType, [
                        'flight_status_change', 'gate_change', 'boarding_call', 
                        'delay', 'cancellation', 'check_in_reminder'
                    ]);
                }
                
                return $preferences->isNotificationTypeEnabled($notificationType);
            });
    }

    /**
     * Dispatch notifications to passengers
     */
    protected function dispatchNotifications(Collection $passengers, $notification): void
    {
        foreach ($passengers as $passenger) {
            try {
                $passenger->notify($notification);
                
                Log::debug('Notification dispatched', [
                    'passenger_id' => $passenger->id,
                    'notification_type' => get_class($notification),
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to dispatch notification', [
                    'passenger_id' => $passenger->id,
                    'notification_type' => get_class($notification),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Process notification retry queue
     */
    public function processRetryQueue(): void
    {
        $failedNotifications = Notification::forRetry()->limit(100)->get();
        
        foreach ($failedNotifications as $notification) {
            try {
                $this->retryNotification($notification);
                
            } catch (\Exception $e) {
                $notification->markAsFailed($e->getMessage());
                
                Log::error('Notification retry failed', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        if ($failedNotifications->count() > 0) {
            Log::info('Processed notification retry queue', [
                'processed_count' => $failedNotifications->count(),
            ]);
        }
    }

    /**
     * Retry a failed notification
     */
    protected function retryNotification(Notification $notification): void
    {
        $passenger = $notification->passenger;
        $flight = $notification->flight;
        
        if (!$passenger || !$flight) {
            $notification->markAsFailed('Passenger or flight not found');
            return;
        }

        // Recreate notification based on type
        $notificationClass = $this->getNotificationClass($notification->type);
        if (!$notificationClass) {
            $notification->markAsFailed('Unknown notification type');
            return;
        }

        $newNotification = new $notificationClass($flight);
        $passenger->notify($newNotification);
        
        // Mark original as delivered
        $notification->markAsDelivered();
    }

    /**
     * Get notification class by type
     */
    protected function getNotificationClass(string $type): ?string
    {
        return match ($type) {
            'flight_status_change' => FlightStatusChangeNotification::class,
            'gate_change' => FlightGateChangeNotification::class,
            'boarding_call' => FlightBoardingNotification::class,
            'delay' => FlightDelayNotification::class,
            'cancellation' => FlightCancellationNotification::class,
            default => null,
        };
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_sent' => Notification::where('created_at', '>=', $startDate)->count(),
            'successful' => Notification::where('created_at', '>=', $startDate)->where('status', 'delivered')->count(),
            'failed' => Notification::where('created_at', '>=', $startDate)->where('status', 'failed')->count(),
            'pending' => Notification::where('created_at', '>=', $startDate)->where('status', 'pending')->count(),
            'by_type' => Notification::where('created_at', '>=', $startDate)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_channel' => Notification::where('created_at', '>=', $startDate)
                ->whereNotNull('delivery_channels')
                ->get()
                ->flatMap(function ($notification) {
                    return $notification->delivery_channels ?? [];
                })
                ->countBy()
                ->toArray(),
        ];
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        $deleted = Notification::where('created_at', '<', now()->subDays($days))
            ->where('status', '!=', 'pending')
            ->delete();
            
        Log::info('Cleaned up old notifications', [
            'deleted_count' => $deleted,
            'older_than_days' => $days,
        ]);
        
        return $deleted;
    }
}
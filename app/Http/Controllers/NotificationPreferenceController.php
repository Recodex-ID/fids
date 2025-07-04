<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\Passenger;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationPreferenceController extends Controller
{
    /**
     * Display notification preferences for a passenger
     */
    public function show(Passenger $passenger)
    {
        $preferences = $passenger->notificationPreferences;
        
        // Create default preferences if none exist
        if (!$preferences) {
            $preferences = NotificationPreference::createDefaultForPassenger($passenger);
        }

        return Inertia::render('NotificationPreferences', [
            'passenger' => $passenger,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update notification preferences for a passenger
     */
    public function update(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'flight_status_changes' => 'boolean',
            'gate_changes' => 'boolean',
            'boarding_calls' => 'boolean',
            'delays' => 'boolean',
            'cancellations' => 'boolean',
            'schedule_changes' => 'boolean',
            'check_in_reminders' => 'boolean',
            'baggage_updates' => 'boolean',
            'boarding_call_advance_minutes' => 'integer|min:10|max:120',
            'delay_notification_threshold' => 'integer|min:5|max:180',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'notification_frequency' => 'in:immediate,batched,summary',
            'language' => 'string|max:5',
            'timezone' => 'nullable|string|max:50',
        ]);

        $preferences = $passenger->notificationPreferences;
        
        if ($preferences) {
            $preferences->update($validated);
        } else {
            $validated['passenger_id'] = $passenger->id;
            $preferences = NotificationPreference::create($validated);
        }

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Store push notification subscription
     */
    public function storePushSubscription(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'p256dh' => 'required|string',
            'auth' => 'required|string',
        ]);

        // Deactivate existing subscriptions for this passenger
        $passenger->pushSubscriptions()->update(['is_active' => false]);

        // Create new subscription
        PushSubscription::create([
            'passenger_id' => $passenger->id,
            'endpoint' => $validated['endpoint'],
            'p256dh_key' => $validated['p256dh'],
            'auth_key' => $validated['auth'],
            'user_agent' => $request->userAgent(),
            'is_active' => true,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove push notification subscription
     */
    public function removePushSubscription(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $passenger->pushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }

    /**
     * Get notification inbox for passenger
     */
    public function inbox(Passenger $passenger)
    {
        $notifications = $passenger->notifications()
            ->with('flight')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return Inertia::render('NotificationInbox', [
            'passenger' => $passenger,
            'initialNotifications' => $notifications,
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        $passenger->notifications()
            ->whereIn('id', $validated['notification_ids'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete notifications
     */
    public function delete(Request $request, Passenger $passenger)
    {
        $validated = $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id',
        ]);

        $passenger->notifications()
            ->whereIn('id', $validated['notification_ids'])
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get notification statistics for passenger
     */
    public function statistics(Passenger $passenger)
    {
        $stats = [
            'total' => $passenger->notifications()->count(),
            'unread' => $passenger->notifications()->whereNull('read_at')->count(),
            'by_type' => $passenger->notifications()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'recent_activity' => $passenger->notifications()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return response()->json($stats);
    }
}

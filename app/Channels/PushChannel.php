<?php

namespace App\Channels;

use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Exception;

class PushChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toPush')) {
            return;
        }

        $pushSubscription = $this->getPushSubscription($notifiable);
        if (!$pushSubscription) {
            $this->logFailure($notifiable, $notification, 'No push subscription available');
            return;
        }

        $pushData = $notification->toPush($notifiable);
        if (empty($pushData)) {
            $this->logFailure($notifiable, $notification, 'Empty push data');
            return;
        }

        try {
            $this->sendWebPushNotification($pushSubscription, $pushData);
            $this->logSuccess($notifiable, $notification);
            
        } catch (Exception $e) {
            $this->logFailure($notifiable, $notification, $e->getMessage());
        }
    }

    /**
     * Get push subscription for the notifiable entity.
     */
    protected function getPushSubscription(object $notifiable): ?array
    {
        // Check if notifiable has push subscription stored
        if (method_exists($notifiable, 'pushSubscription') && $notifiable->pushSubscription) {
            return $notifiable->pushSubscription->toArray();
        }

        // For demo purposes, we'll create a mock subscription
        // In real implementation, this would come from a push_subscriptions table
        return null;
    }

    /**
     * Send web push notification using Web Push Protocol
     */
    protected function sendWebPushNotification(array $subscription, array $pushData): void
    {
        $payload = json_encode([
            'title' => $pushData['title'] ?? 'Flight Notification',
            'body' => $pushData['body'] ?? '',
            'icon' => '/icons/flight-icon.png',
            'badge' => '/icons/badge.png',
            'data' => $pushData['data'] ?? [],
            'actions' => $this->getNotificationActions($pushData),
            'requireInteraction' => in_array($pushData['priority'] ?? 'normal', ['high', 'urgent']),
            'timestamp' => now()->timestamp * 1000, // JavaScript timestamp
        ]);

        // For production, you would use a proper Web Push library like:
        // https://github.com/web-push-libs/web-push-php
        
        // Mock implementation for demo
        \Log::info('Web push notification would be sent', [
            'subscription' => $subscription,
            'payload' => $payload,
        ]);
    }

    /**
     * Get notification actions based on push data
     */
    protected function getNotificationActions(array $pushData): array
    {
        $type = $pushData['data']['type'] ?? 'default';
        
        return match ($type) {
            'gate_change' => [
                ['action' => 'view_gate', 'title' => 'View Gate Info'],
                ['action' => 'dismiss', 'title' => 'Dismiss'],
            ],
            'boarding_call' => [
                ['action' => 'view_boarding_pass', 'title' => 'View Boarding Pass'],
                ['action' => 'get_directions', 'title' => 'Get Directions'],
            ],
            'delay' => [
                ['action' => 'view_details', 'title' => 'View Details'],
                ['action' => 'rebooking_options', 'title' => 'Rebooking Options'],
            ],
            'cancellation' => [
                ['action' => 'contact_support', 'title' => 'Contact Support'],
                ['action' => 'rebooking_options', 'title' => 'Rebooking Options'],
            ],
            default => [
                ['action' => 'view_flight', 'title' => 'View Flight'],
                ['action' => 'dismiss', 'title' => 'Dismiss'],
            ],
        };
    }

    /**
     * Log successful push notification delivery
     */
    protected function logSuccess(object $notifiable, Notification $notification): void
    {
        $this->createNotificationRecord($notifiable, $notification, 'sent', null, [
            'channel' => 'push',
        ]);

        \Log::info('Push notification sent successfully', [
            'notifiable_id' => $notifiable->id ?? null,
            'notification_type' => get_class($notification),
        ]);
    }

    /**
     * Log failed push notification delivery
     */
    protected function logFailure(object $notifiable, Notification $notification, string $reason): void
    {
        $this->createNotificationRecord($notifiable, $notification, 'failed', $reason, [
            'channel' => 'push',
        ]);

        \Log::error('Push notification failed', [
            'notifiable_id' => $notifiable->id ?? null,
            'notification_type' => get_class($notification),
            'reason' => $reason,
        ]);
    }

    /**
     * Create notification record in database
     */
    protected function createNotificationRecord(
        object $notifiable, 
        Notification $notification, 
        string $status, 
        ?string $failureReason = null,
        array $metadata = []
    ): void {
        if (!method_exists($notification, 'toDatabase')) {
            return;
        }

        $data = $notification->toDatabase($notifiable);
        
        NotificationModel::create([
            'passenger_id' => $notifiable->id ?? null,
            'flight_id' => $data['flight_id'] ?? null,
            'type' => $data['type'] ?? 'unknown',
            'message' => is_string($data['message']) ? $data['message'] : json_encode($data['message']),
            'status' => $status,
            'delivery_channels' => ['push'],
            'sent_at' => $status === 'sent' ? now() : null,
            'delivered_at' => $status === 'sent' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
            'failure_reason' => $failureReason,
            'notification_id' => $data['notification_id'] ?? \Str::uuid(),
            'metadata' => array_merge($data['metadata'] ?? [], $metadata),
            'priority' => $data['priority'] ?? 'normal',
            'template_name' => $data['template_name'] ?? null,
        ]);
    }
}
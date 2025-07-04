<?php

namespace App\Channels;

use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;
use Twilio\Rest\Client;
use Exception;

class SmsChannel
{
    protected Client $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phoneNumber = $this->getPhoneNumber($notifiable);
        if (!$phoneNumber) {
            $this->logFailure($notifiable, $notification, 'No phone number available');
            return;
        }

        $message = $notification->toSms($notifiable);
        if (empty($message)) {
            $this->logFailure($notifiable, $notification, 'Empty message content');
            return;
        }

        try {
            $response = $this->twilio->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message,
                ]
            );

            $this->logSuccess($notifiable, $notification, $response->sid);
            
        } catch (Exception $e) {
            $this->logFailure($notifiable, $notification, $e->getMessage());
        }
    }

    /**
     * Get the phone number for the notifiable entity.
     */
    protected function getPhoneNumber(object $notifiable): ?string
    {
        if (isset($notifiable->phone)) {
            return $this->formatPhoneNumber($notifiable->phone);
        }

        return null;
    }

    /**
     * Format phone number for international SMS
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming US +1 for demo)
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        return '+' . $phone;
    }

    /**
     * Log successful SMS delivery
     */
    protected function logSuccess(object $notifiable, Notification $notification, string $twilioSid): void
    {
        $this->createNotificationRecord($notifiable, $notification, 'sent', null, [
            'twilio_sid' => $twilioSid,
            'channel' => 'sms',
        ]);

        \Log::info('SMS notification sent successfully', [
            'notifiable_id' => $notifiable->id ?? null,
            'notification_type' => get_class($notification),
            'twilio_sid' => $twilioSid,
        ]);
    }

    /**
     * Log failed SMS delivery
     */
    protected function logFailure(object $notifiable, Notification $notification, string $reason): void
    {
        $this->createNotificationRecord($notifiable, $notification, 'failed', $reason, [
            'channel' => 'sms',
        ]);

        \Log::error('SMS notification failed', [
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
            'delivery_channels' => ['sms'],
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
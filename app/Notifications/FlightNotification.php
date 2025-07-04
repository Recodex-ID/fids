<?php

namespace App\Notifications;

use App\Models\Flight;
use App\Models\NotificationTemplate;
use App\Models\Passenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

abstract class FlightNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Flight $flight;
    protected string $notificationType;
    protected array $templateData;
    protected string $priority = 'normal';

    /**
     * Create a new notification instance.
     */
    public function __construct(Flight $flight, array $templateData = [])
    {
        $this->flight = $flight;
        $this->templateData = $templateData;
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (!$notifiable instanceof Passenger) {
            return ['database'];
        }

        $preferences = $notifiable->notificationPreferences;
        if (!$preferences) {
            return ['database', 'mail']; // Default channels
        }

        return $preferences->getChannelsForNotificationType($this->notificationType);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $template = $this->getTemplate('mail', $notifiable);
        if (!$template) {
            return $this->getDefaultMailMessage();
        }

        $rendered = $template->render($this->getTemplateData($notifiable));
        $template->incrementUsage();

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->line($rendered['content'])
            ->when($rendered['html_content'], function ($mail) use ($rendered) {
                return $mail->view('emails.flight-notification', [
                    'content' => $rendered['html_content']
                ]);
            });
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $template = $this->getTemplate('database', $notifiable);
        $content = $template ? $template->render($this->getTemplateData($notifiable))['content'] : $this->getDefaultMessage();

        return [
            'type' => $this->notificationType,
            'flight_id' => $this->flight->id,
            'message' => $content,
            'priority' => $this->priority,
            'template_name' => $template?->name,
            'notification_id' => Str::uuid(),
            'metadata' => [
                'flight_number' => $this->flight->flight_number,
                'passenger_id' => $notifiable->id ?? null,
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $template = $this->getTemplate('sms', $notifiable);
        if (!$template) {
            return $this->getDefaultMessage();
        }

        $rendered = $template->render($this->getTemplateData($notifiable));
        $template->incrementUsage();

        return $rendered['content'];
    }

    /**
     * Get the push notification representation.
     */
    public function toPush(object $notifiable): array
    {
        $template = $this->getTemplate('push', $notifiable);
        if (!$template) {
            return [
                'title' => 'Flight Update',
                'body' => $this->getDefaultMessage(),
                'priority' => $this->priority,
            ];
        }

        $rendered = $template->render($this->getTemplateData($notifiable));
        $template->incrementUsage();

        return [
            'title' => $rendered['subject'] ?? 'Flight Update',
            'body' => $rendered['content'],
            'priority' => $this->priority,
            'data' => [
                'flight_id' => $this->flight->id,
                'flight_number' => $this->flight->flight_number,
                'type' => $this->notificationType,
            ],
        ];
    }

    /**
     * Get template for specific channel
     */
    protected function getTemplate(string $channel, object $notifiable): ?NotificationTemplate
    {
        $language = $notifiable->notificationPreferences?->language ?? 'en';
        
        return NotificationTemplate::getBestTemplate(
            $this->notificationType,
            $channel,
            $language
        );
    }

    /**
     * Get template data for rendering
     */
    protected function getTemplateData(object $notifiable): array
    {
        $baseData = [
            'passenger_name' => $notifiable->name ?? 'Passenger',
            'flight_number' => $this->flight->flight_number,
            'gate' => $this->flight->gate,
            'seat_number' => $notifiable->seat_number ?? 'N/A',
            'booking_reference' => $notifiable->booking_reference ?? 'N/A',
            'departure_time' => $this->flight->scheduled_departure?->format('H:i'),
            'arrival_time' => $this->flight->scheduled_arrival?->format('H:i'),
            'origin_city' => $this->flight->originAirport->city ?? 'Unknown',
            'destination_city' => $this->flight->destinationAirport->city ?? 'Unknown',
        ];

        return array_merge($baseData, $this->templateData);
    }

    /**
     * Get default mail message when no template is available
     */
    protected function getDefaultMailMessage(): MailMessage
    {
        return (new MailMessage)
            ->subject('Flight Update - ' . $this->flight->flight_number)
            ->line($this->getDefaultMessage())
            ->line('Thank you for flying with us!');
    }

    /**
     * Abstract method to get default message
     */
    abstract protected function getDefaultMessage(): string;

    /**
     * Get notification type
     */
    public function getNotificationType(): string
    {
        return $this->notificationType;
    }

    /**
     * Set notification priority
     */
    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
}
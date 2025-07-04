<?php

namespace App\Mail;

use App\Models\Flight;
use App\Models\NotificationTemplate;
use App\Models\Passenger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FlightNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Flight $flight;
    public Passenger $passenger;
    public string $notificationType;
    public array $templateData;
    public ?NotificationTemplate $template;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Flight $flight, 
        Passenger $passenger, 
        string $notificationType, 
        array $templateData = []
    ) {
        $this->flight = $flight;
        $this->passenger = $passenger;
        $this->notificationType = $notificationType;
        $this->templateData = $templateData;
        $this->template = $this->getTemplate();
        
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubject();
        
        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            tags: ['flight-notification', $this->notificationType],
            metadata: [
                'flight_id' => $this->flight->id,
                'passenger_id' => $this->passenger->id,
                'notification_type' => $this->notificationType,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $viewData = $this->getViewData();
        
        return new Content(
            view: 'emails.flight-notification',
            text: 'emails.flight-notification-text',
            with: $viewData,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        // Add boarding pass for boarding notifications
        if ($this->notificationType === 'boarding_call' && $this->passenger->seat_number) {
            // In a real implementation, you would generate a PDF boarding pass
            // $attachments[] = Attachment::fromPath($this->generateBoardingPass());
        }
        
        return $attachments;
    }

    /**
     * Get email template for this notification type
     */
    protected function getTemplate(): ?NotificationTemplate
    {
        $language = $this->passenger->notificationPreferences?->language ?? 'en';
        
        return NotificationTemplate::getBestTemplate(
            $this->notificationType,
            'mail',
            $language
        );
    }

    /**
     * Get email subject
     */
    protected function getSubject(): string
    {
        if ($this->template) {
            $rendered = $this->template->render($this->getTemplateData());
            return $rendered['subject'] ?? $this->getDefaultSubject();
        }
        
        return $this->getDefaultSubject();
    }

    /**
     * Get view data for email template
     */
    protected function getViewData(): array
    {
        $baseData = [
            'flight' => $this->flight,
            'passenger' => $this->passenger,
            'notificationType' => $this->notificationType,
            'templateData' => $this->templateData,
        ];

        if ($this->template) {
            $rendered = $this->template->render($this->getTemplateData());
            $baseData['renderedContent'] = $rendered;
            $baseData['template'] = $this->template;
        } else {
            $baseData['renderedContent'] = [
                'subject' => $this->getDefaultSubject(),
                'content' => $this->getDefaultContent(),
                'html_content' => null,
            ];
        }

        return $baseData;
    }

    /**
     * Get template data for rendering
     */
    protected function getTemplateData(): array
    {
        $baseData = [
            'passenger_name' => $this->passenger->name,
            'flight_number' => $this->flight->flight_number,
            'gate' => $this->flight->gate,
            'seat_number' => $this->passenger->seat_number ?? 'N/A',
            'booking_reference' => $this->passenger->booking_reference,
            'departure_time' => $this->flight->scheduled_departure?->format('H:i'),
            'arrival_time' => $this->flight->scheduled_arrival?->format('H:i'),
            'departure_date' => $this->flight->scheduled_departure?->format('Y-m-d'),
            'origin_city' => $this->flight->originAirport->city ?? 'Unknown',
            'destination_city' => $this->flight->destinationAirport->city ?? 'Unknown',
            'origin_airport' => $this->flight->originAirport->name ?? 'Unknown',
            'destination_airport' => $this->flight->destinationAirport->name ?? 'Unknown',
            'airline_name' => $this->flight->airline->name ?? 'Unknown',
        ];

        return array_merge($baseData, $this->templateData);
    }

    /**
     * Get default subject when no template available
     */
    protected function getDefaultSubject(): string
    {
        return match ($this->notificationType) {
            'gate_change' => "Gate Change: Flight {$this->flight->flight_number}",
            'boarding_call' => "Now Boarding: Flight {$this->flight->flight_number}",
            'delay' => "Flight Delay: {$this->flight->flight_number}",
            'cancellation' => "Flight Cancelled: {$this->flight->flight_number}",
            default => "Flight Update: {$this->flight->flight_number}",
        };
    }

    /**
     * Get default content when no template available
     */
    protected function getDefaultContent(): string
    {
        return match ($this->notificationType) {
            'gate_change' => "Your flight {$this->flight->flight_number} gate has changed. Please check the latest information.",
            'boarding_call' => "Your flight {$this->flight->flight_number} is now boarding. Please proceed to gate {$this->flight->gate}.",
            'delay' => "Your flight {$this->flight->flight_number} has been delayed. Please check for updated departure times.",
            'cancellation' => "Your flight {$this->flight->flight_number} has been cancelled. Please contact customer service.",
            default => "Your flight {$this->flight->flight_number} has been updated. Please check the latest information.",
        };
    }
}

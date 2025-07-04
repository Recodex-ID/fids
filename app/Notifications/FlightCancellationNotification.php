<?php

namespace App\Notifications;

use App\Models\Flight;

class FlightCancellationNotification extends FlightNotification
{
    protected string $notificationType = 'cancellation';
    protected string $priority = 'urgent';

    public function __construct(Flight $flight, string $reason = null)
    {
        $templateData = [
            'cancellation_reason' => $reason ?? 'Operational reasons',
        ];

        parent::__construct($flight, $templateData);
    }

    protected function getDefaultMessage(): string
    {
        return "Flight {$this->flight->flight_number} has been cancelled. Please contact customer service for rebooking options.";
    }
}
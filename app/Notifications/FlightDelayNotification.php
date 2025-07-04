<?php

namespace App\Notifications;

use App\Models\Flight;

class FlightDelayNotification extends FlightNotification
{
    protected string $notificationType = 'delay';
    protected string $priority = 'high';

    public function __construct(Flight $flight, int $delayMinutes = 0, string $reason = null)
    {
        $templateData = [
            'delay_minutes' => $delayMinutes,
            'delay_reason' => $reason ?? 'Operational reasons',
            'new_departure_time' => $flight->actual_departure?->format('H:i') ?? 'TBD',
        ];

        parent::__construct($flight, $templateData);
    }

    protected function getDefaultMessage(): string
    {
        $delayMinutes = $this->templateData['delay_minutes'];
        $newTime = $this->templateData['new_departure_time'];
        
        return "Flight {$this->flight->flight_number} has been delayed by {$delayMinutes} minutes. New departure time: {$newTime}.";
    }
}
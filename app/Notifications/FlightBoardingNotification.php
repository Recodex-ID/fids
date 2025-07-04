<?php

namespace App\Notifications;

use App\Models\Flight;

class FlightBoardingNotification extends FlightNotification
{
    protected string $notificationType = 'boarding_call';
    protected string $priority = 'urgent';

    public function __construct(Flight $flight)
    {
        parent::__construct($flight);
    }

    protected function getDefaultMessage(): string
    {
        return "Now boarding: Flight {$this->flight->flight_number} at gate {$this->flight->gate}. Please proceed to the gate with your boarding pass.";
    }
}
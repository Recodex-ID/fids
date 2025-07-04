<?php

namespace App\Notifications;

use App\Models\Flight;

class FlightStatusChangeNotification extends FlightNotification
{
    protected string $notificationType = 'flight_status_change';
    protected string $priority = 'normal';

    public function __construct(Flight $flight, string $oldStatus = null, string $newStatus = null)
    {
        $templateData = [
            'old_status' => $oldStatus ?? 'Unknown',
            'new_status' => $newStatus ?? $flight->status,
        ];

        parent::__construct($flight, $templateData);
    }

    protected function getDefaultMessage(): string
    {
        $oldStatus = $this->templateData['old_status'];
        $newStatus = $this->templateData['new_status'];
        
        return "Flight {$this->flight->flight_number} status has changed from {$oldStatus} to {$newStatus}.";
    }
}
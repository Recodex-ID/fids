<?php

namespace App\Notifications;

use App\Models\Flight;

class FlightGateChangeNotification extends FlightNotification
{
    protected string $notificationType = 'gate_change';
    protected string $priority = 'high';

    public function __construct(Flight $flight, string $oldGate = null, string $newGate = null)
    {
        $templateData = [
            'old_gate' => $oldGate ?? 'N/A',
            'new_gate' => $newGate ?? $flight->gate,
        ];

        parent::__construct($flight, $templateData);
    }

    protected function getDefaultMessage(): string
    {
        $oldGate = $this->templateData['old_gate'];
        $newGate = $this->templateData['new_gate'];
        
        return "Gate change for flight {$this->flight->flight_number}: moved from gate {$oldGate} to gate {$newGate}. Please proceed to the new gate.";
    }
}
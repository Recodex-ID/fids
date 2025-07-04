{{ $renderedContent['subject'] ?? 'Flight Notification' }}
================================================================================

Dear {{ $passenger->name }},

{{ $renderedContent['content'] }}

FLIGHT INFORMATION
--------------------------------------------------------------------------------
Flight Number: {{ $flight->flight_number }}
Route: {{ $flight->originAirport->code ?? 'N/A' }} ({{ $flight->originAirport->city ?? 'Unknown' }}) → {{ $flight->destinationAirport->code ?? 'N/A' }} ({{ $flight->destinationAirport->city ?? 'Unknown' }})
Departure: {{ $flight->scheduled_departure?->format('M j, Y \a\t H:i') ?? 'TBD' }}
Arrival: {{ $flight->scheduled_arrival?->format('M j, Y \a\t H:i') ?? 'TBD' }}
@if($flight->gate)
Gate: {{ $flight->gate }}
@endif
@if($passenger->seat_number)
Seat: {{ $passenger->seat_number }}
@endif
Status: {{ ucfirst(str_replace('_', ' ', $notificationType)) }}

PASSENGER INFORMATION
--------------------------------------------------------------------------------
Name: {{ $passenger->name }}
Booking Reference: {{ $passenger->booking_reference }}
@if($flight->airline)
Airline: {{ $flight->airline->name }}
@endif

@if($notificationType === 'boarding_call')
→ View your boarding pass: {{ config('app.url') }}/passengers/boarding-pass
@elseif($notificationType === 'gate_change')
→ Get directions to new gate: {{ config('app.url') }}/flights/{{ $flight->id }}
@elseif($notificationType === 'cancellation')
→ View rebooking options: {{ config('app.url') }}/passengers/rebooking
@else
→ View full flight details: {{ config('app.url') }}/flights/{{ $flight->id }}
@endif

================================================================================
Flight Information Display System
Notification sent: {{ now()->format('M j, Y \a\t H:i') }}

Manage your notification preferences: {{ config('app.url') }}/notifications/preferences
Contact support: {{ config('app.url') }}/support/contact

This is an automated message. Please do not reply to this email.
================================================================================
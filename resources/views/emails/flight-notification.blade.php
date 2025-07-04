<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $renderedContent['subject'] ?? 'Flight Notification' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .flight-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .flight-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .flight-number {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
        }
        .flight-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-urgent { background: #ff6b6b; color: white; }
        .status-high { background: #ffa726; color: white; }
        .status-normal { background: #66bb6a; color: white; }
        .flight-route {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            text-align: center;
        }
        .airport {
            flex: 1;
        }
        .airport-code {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }
        .airport-name {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .flight-arrow {
            margin: 0 20px;
            color: #667eea;
            font-size: 20px;
        }
        .flight-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .detail-item {
            text-align: center;
            padding: 12px;
            background: white;
            border-radius: 8px;
        }
        .detail-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .notification-content {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .notification-content.urgent {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        .notification-content.high {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        .message-content {
            font-size: 16px;
            line-height: 1.6;
        }
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                box-shadow: none;
            }
            .flight-header {
                flex-direction: column;
                gap: 10px;
            }
            .flight-route {
                flex-direction: column;
                gap: 16px;
            }
            .flight-arrow {
                transform: rotate(90deg);
                margin: 10px 0;
            }
            .flight-details {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="flight-icon">✈️</div>
            <h1>{{ $renderedContent['subject'] ?? 'Flight Notification' }}</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Greeting -->
            <p>Dear {{ $passenger->name }},</p>

            <!-- Notification Message -->
            <div class="notification-content {{ $notificationType === 'boarding_call' || $notificationType === 'cancellation' ? 'urgent' : ($notificationType === 'gate_change' || $notificationType === 'delay' ? 'high' : '') }}">
                @if($renderedContent['html_content'])
                    {!! $renderedContent['html_content'] !!}
                @else
                    <div class="message-content">
                        {{ $renderedContent['content'] }}
                    </div>
                @endif
            </div>

            <!-- Flight Information Card -->
            <div class="flight-card">
                <div class="flight-header">
                    <div class="flight-number">{{ $flight->flight_number }}</div>
                    <div class="flight-status status-{{ $notificationType === 'boarding_call' || $notificationType === 'cancellation' ? 'urgent' : ($notificationType === 'gate_change' || $notificationType === 'delay' ? 'high' : 'normal') }}">
                        {{ ucfirst(str_replace('_', ' ', $notificationType)) }}
                    </div>
                </div>

                <div class="flight-route">
                    <div class="airport">
                        <div class="airport-code">{{ $flight->originAirport->code ?? 'N/A' }}</div>
                        <div class="airport-name">{{ $flight->originAirport->city ?? 'Unknown' }}</div>
                    </div>
                    <div class="flight-arrow">→</div>
                    <div class="airport">
                        <div class="airport-code">{{ $flight->destinationAirport->code ?? 'N/A' }}</div>
                        <div class="airport-name">{{ $flight->destinationAirport->city ?? 'Unknown' }}</div>
                    </div>
                </div>

                <div class="flight-details">
                    <div class="detail-item">
                        <div class="detail-label">Departure</div>
                        <div class="detail-value">{{ $flight->scheduled_departure?->format('M j, H:i') ?? 'TBD' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Arrival</div>
                        <div class="detail-value">{{ $flight->scheduled_arrival?->format('M j, H:i') ?? 'TBD' }}</div>
                    </div>
                    @if($flight->gate)
                    <div class="detail-item">
                        <div class="detail-label">Gate</div>
                        <div class="detail-value">{{ $flight->gate }}</div>
                    </div>
                    @endif
                    @if($passenger->seat_number)
                    <div class="detail-item">
                        <div class="detail-label">Seat</div>
                        <div class="detail-value">{{ $passenger->seat_number }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Call to Action -->
            @if($notificationType === 'boarding_call')
                <a href="{{ config('app.url') }}/passengers/boarding-pass" class="cta-button">
                    View Boarding Pass
                </a>
            @elseif($notificationType === 'gate_change')
                <a href="{{ config('app.url') }}/flights/{{ $flight->id }}" class="cta-button">
                    Get Directions to Gate
                </a>
            @elseif($notificationType === 'cancellation')
                <a href="{{ config('app.url') }}/passengers/rebooking" class="cta-button">
                    View Rebooking Options
                </a>
            @else
                <a href="{{ config('app.url') }}/flights/{{ $flight->id }}" class="cta-button">
                    View Flight Details
                </a>
            @endif

            <!-- Additional Information -->
            <p style="margin-top: 30px; font-size: 14px; color: #666;">
                <strong>Booking Reference:</strong> {{ $passenger->booking_reference }}<br>
                @if($flight->airline)
                    <strong>Airline:</strong> {{ $flight->airline->name }}<br>
                @endif
                <strong>Notification sent:</strong> {{ now()->format('M j, Y \a\t H:i') }}
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated notification from Flight Information Display System.</p>
            <p>
                <a href="{{ config('app.url') }}/notifications/preferences">Manage Notification Preferences</a> |
                <a href="{{ config('app.url') }}/support/contact">Contact Support</a>
            </p>
            <p style="margin-top: 20px; font-size: 12px;">
                If you no longer wish to receive these notifications, you can update your preferences in your account settings.
            </p>
        </div>
    </div>
</body>
</html>
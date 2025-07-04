import React from 'react';

interface Flight {
    id: number;
    flight_number: string;
    status: string;
    airline: {
        id: number;
        name: string;
        code: string;
    };
    origin_airport: {
        id: number;
        name: string;
        code: string;
        city: string;
    };
    destination_airport: {
        id: number;
        name: string;
        code: string;
        city: string;
    };
    scheduled_departure: string;
    scheduled_arrival: string;
    actual_departure?: string;
    actual_arrival?: string;
    gate?: string;
    aircraft_type?: string;
}

interface FlightCardProps {
    flight: Flight;
    size?: 'small' | 'medium' | 'large';
    showAirline?: boolean;
    showGate?: boolean;
    showStatus?: boolean;
}

const FlightCard: React.FC<FlightCardProps> = ({ 
    flight, 
    size = 'medium',
    showAirline = true,
    showGate = true,
    showStatus = true 
}) => {
    const getStatusColor = (status: string): string => {
        switch (status.toLowerCase()) {
            case 'on_time':
            case 'arrived':
            case 'departed':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'delayed':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            case 'boarding':
                return 'bg-blue-100 text-blue-800 border-blue-200';
            case 'cancelled':
                return 'bg-red-100 text-red-800 border-red-200';
            case 'check_in':
                return 'bg-purple-100 text-purple-800 border-purple-200';
            default:
                return 'bg-gray-100 text-gray-800 border-gray-200';
        }
    };

    const getStatusText = (status: string): string => {
        switch (status.toLowerCase()) {
            case 'on_time': return 'ON TIME';
            case 'delayed': return 'DELAYED';
            case 'boarding': return 'BOARDING';
            case 'departed': return 'DEPARTED';
            case 'arrived': return 'ARRIVED';
            case 'cancelled': return 'CANCELLED';
            case 'check_in': return 'CHECK-IN';
            default: return status.toUpperCase();
        }
    };

    const formatTime = (timeString: string): string => {
        if (!timeString) return '--:--';
        return new Date(timeString).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    };

    const sizeClasses = {
        small: {
            card: 'p-3',
            flightNumber: 'text-lg',
            text: 'text-sm',
            time: 'text-base',
            badge: 'px-2 py-1 text-xs',
            gate: 'w-8 h-8 text-sm'
        },
        medium: {
            card: 'p-4',
            flightNumber: 'text-xl',
            text: 'text-base',
            time: 'text-lg',
            badge: 'px-3 py-1 text-sm',
            gate: 'w-10 h-10 text-base'
        },
        large: {
            card: 'p-6',
            flightNumber: 'text-2xl',
            text: 'text-lg',
            time: 'text-xl',
            badge: 'px-4 py-2 text-base',
            gate: 'w-12 h-12 text-lg'
        }
    };

    const classes = sizeClasses[size];

    return (
        <div className={`bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow ${classes.card}`}>
            {/* Header */}
            <div className="flex items-center justify-between mb-3">
                <div className={`font-mono font-bold text-gray-900 ${classes.flightNumber}`}>
                    {flight.flight_number}
                </div>
                {showStatus && (
                    <span className={`inline-flex items-center rounded-full border font-medium ${classes.badge} ${getStatusColor(flight.status)}`}>
                        {getStatusText(flight.status)}
                    </span>
                )}
            </div>

            {/* Airline */}
            {showAirline && (
                <div className={`text-gray-600 mb-3 ${classes.text}`}>
                    <div className="font-medium">{flight.airline.name}</div>
                    <div className="text-gray-500 text-sm">{flight.airline.code}</div>
                </div>
            )}

            {/* Route */}
            <div className={`mb-4 ${classes.text}`}>
                <div className="flex items-center justify-between">
                    <div className="text-center">
                        <div className={`font-bold text-gray-900 ${classes.time}`}>
                            {flight.origin_airport.code}
                        </div>
                        <div className="text-gray-500 text-xs">{flight.origin_airport.city}</div>
                    </div>
                    <div className="flex-1 px-3">
                        <div className="border-t border-gray-300 relative">
                            <div className="absolute inset-0 flex items-center justify-center">
                                <div className="bg-white px-2">
                                    <span className="text-gray-400">✈</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="text-center">
                        <div className={`font-bold text-gray-900 ${classes.time}`}>
                            {flight.destination_airport.code}
                        </div>
                        <div className="text-gray-500 text-xs">{flight.destination_airport.city}</div>
                    </div>
                </div>
            </div>

            {/* Time and Gate */}
            <div className="flex items-center justify-between">
                <div className="text-center">
                    <div className="text-gray-500 text-xs mb-1">SCHEDULED</div>
                    <div className={`font-mono font-medium text-gray-900 ${classes.time}`}>
                        {formatTime(flight.scheduled_departure)}
                    </div>
                </div>
                
                {flight.actual_departure && (
                    <div className="text-center">
                        <div className="text-gray-500 text-xs mb-1">ACTUAL</div>
                        <div className={`font-mono font-medium text-blue-600 ${classes.time}`}>
                            {formatTime(flight.actual_departure)}
                        </div>
                    </div>
                )}

                {showGate && flight.gate && (
                    <div className="text-center">
                        <div className="text-gray-500 text-xs mb-1">GATE</div>
                        <div className={`inline-flex items-center justify-center bg-blue-100 text-blue-800 font-bold rounded ${classes.gate}`}>
                            {flight.gate}
                        </div>
                    </div>
                )}
            </div>

            {/* Additional info for large cards */}
            {size === 'large' && flight.aircraft_type && (
                <div className="mt-3 pt-3 border-t border-gray-200">
                    <div className="text-gray-500 text-sm">
                        Aircraft: <span className="font-medium text-gray-700">{flight.aircraft_type}</span>
                    </div>
                </div>
            )}
        </div>
    );
};

export default FlightCard;
import React, { useState, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import { useRealTimeFlights } from '../hooks/useRealTimeFlights';

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
    created_at: string;
    updated_at: string;
}

interface Props {
    initialFlights: Flight[];
    refreshInterval?: number;
    kioskMode?: boolean;
    defaultFilter?: 'all' | 'departures' | 'arrivals';
}

const DisplayBoard: React.FC<Props> = ({ 
    initialFlights, 
    refreshInterval = 30000, 
    kioskMode = false,
    defaultFilter = 'all'
}) => {
    const { flights, connectionStatus, lastUpdate, setFlights } = useRealTimeFlights(initialFlights);
    const [currentTime, setCurrentTime] = useState(new Date());
    const [isFullscreen, setIsFullscreen] = useState(kioskMode);
    const [filter, setFilter] = useState<'all' | 'departures' | 'arrivals'>(defaultFilter);
    const [autoRefresh, setAutoRefresh] = useState(true);

    // Update current time every second
    useEffect(() => {
        const timer = setInterval(() => {
            setCurrentTime(new Date());
        }, 1000);

        return () => clearInterval(timer);
    }, []);

    // Auto-refresh data
    useEffect(() => {
        if (!autoRefresh) return;

        const refreshTimer = setInterval(async () => {
            try {
                const response = await fetch('/api/display-board/refresh');
                const data = await response.json();
                setFlights(data.flights || []);
            } catch (error) {
                console.error('Failed to refresh flight data:', error);
            }
        }, refreshInterval);

        return () => clearInterval(refreshTimer);
    }, [autoRefresh, refreshInterval, setFlights]);

    // Fullscreen toggle
    const toggleFullscreen = useCallback(() => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().then(() => {
                setIsFullscreen(true);
            }).catch((err) => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen().then(() => {
                setIsFullscreen(false);
            }).catch((err) => {
                console.error('Error attempting to exit fullscreen:', err);
            });
        }
    }, []);

    // Keyboard shortcuts
    useEffect(() => {
        const handleKeyPress = (event: KeyboardEvent) => {
            switch (event.key) {
                case 'F11':
                    event.preventDefault();
                    toggleFullscreen();
                    break;
                case '1':
                    setFilter('all');
                    break;
                case '2':
                    setFilter('departures');
                    break;
                case '3':
                    setFilter('arrivals');
                    break;
                case ' ':
                    event.preventDefault();
                    setAutoRefresh(prev => !prev);
                    break;
            }
        };

        window.addEventListener('keydown', handleKeyPress);
        return () => window.removeEventListener('keydown', handleKeyPress);
    }, [toggleFullscreen]);

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

    const formatDate = (date: Date): string => {
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const filteredFlights = flights.filter(flight => {
        switch (filter) {
            case 'departures':
                return ['on_time', 'delayed', 'boarding', 'check_in'].includes(flight.status);
            case 'arrivals':
                return ['arrived', 'delayed', 'on_time'].includes(flight.status);
            default:
                return true;
        }
    }).sort((a, b) => {
        // Sort by scheduled departure time
        return new Date(a.scheduled_departure).getTime() - new Date(b.scheduled_departure).getTime();
    });

    return (
        <>
            <Head title="Flight Information Display Board" />
            
            <div className={`min-h-screen bg-gray-50 ${isFullscreen ? 'p-2' : 'p-4'}`}>
                {/* Header */}
                <div className={`bg-white rounded-lg shadow-lg ${isFullscreen ? 'p-4 mb-2' : 'p-6 mb-6'}`}>
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <h1 className={`font-bold text-gray-900 ${isFullscreen ? 'text-3xl' : 'text-4xl'}`}>
                                Flight Information Display
                            </h1>
                            <p className={`text-gray-600 ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                {formatDate(currentTime)}
                            </p>
                        </div>
                        
                        <div className="flex flex-col lg:flex-row items-center gap-4">
                            {/* Current Time */}
                            <div className="text-center">
                                <div className={`font-mono font-bold text-blue-600 ${isFullscreen ? 'text-2xl' : 'text-3xl'}`}>
                                    {currentTime.toLocaleTimeString('en-US', { 
                                        hour: '2-digit', 
                                        minute: '2-digit', 
                                        second: '2-digit',
                                        hour12: false 
                                    })}
                                </div>
                                <div className="text-sm text-gray-500">Local Time</div>
                            </div>

                            {/* Connection Status */}
                            <div className="flex items-center gap-2">
                                <div className={`w-3 h-3 rounded-full ${
                                    connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'
                                }`}></div>
                                <span className={`text-sm font-medium ${
                                    connectionStatus === 'connected' ? 'text-green-600' : 'text-red-600'
                                }`}>
                                    {connectionStatus === 'connected' ? 'LIVE' : 'OFFLINE'}
                                </span>
                            </div>

                            {/* Auto-refresh indicator */}
                            <div className="flex items-center gap-2">
                                <div className={`w-2 h-2 rounded-full ${autoRefresh ? 'bg-blue-500 animate-pulse' : 'bg-gray-400'}`}></div>
                                <span className="text-sm text-gray-600">
                                    {autoRefresh ? 'AUTO-REFRESH' : 'MANUAL'}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Controls */}
                    {!kioskMode && (
                        <div className="mt-4 flex flex-wrap gap-2">
                            <button
                                onClick={() => setFilter('all')}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                    filter === 'all'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                }`}
                            >
                                All Flights
                            </button>
                            <button
                                onClick={() => setFilter('departures')}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                    filter === 'departures'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                }`}
                            >
                                Departures
                            </button>
                            <button
                                onClick={() => setFilter('arrivals')}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                    filter === 'arrivals'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                }`}
                            >
                                Arrivals
                            </button>
                            <button
                                onClick={() => setAutoRefresh(!autoRefresh)}
                                className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                                    autoRefresh
                                        ? 'bg-green-600 text-white'
                                        : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                }`}
                            >
                                {autoRefresh ? 'Auto-Refresh ON' : 'Auto-Refresh OFF'}
                            </button>
                            <button
                                onClick={toggleFullscreen}
                                className="px-4 py-2 rounded-md text-sm font-medium bg-gray-200 text-gray-700 hover:bg-gray-300 transition-colors"
                            >
                                {isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'}
                            </button>
                        </div>
                    )}
                </div>

                {/* Flight Display Table */}
                <div className="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-900 text-white">
                                <tr>
                                    <th className={`px-4 py-4 text-left font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        FLIGHT
                                    </th>
                                    <th className={`px-4 py-4 text-left font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        AIRLINE
                                    </th>
                                    <th className={`px-4 py-4 text-left font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        ROUTE
                                    </th>
                                    <th className={`px-4 py-4 text-center font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        SCHEDULED
                                    </th>
                                    <th className={`px-4 py-4 text-center font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        ACTUAL
                                    </th>
                                    <th className={`px-4 py-4 text-center font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        GATE
                                    </th>
                                    <th className={`px-4 py-4 text-center font-bold ${isFullscreen ? 'text-lg' : 'text-xl'}`}>
                                        STATUS
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {filteredFlights.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-12 text-center text-gray-500">
                                            <div className="text-6xl mb-4">✈️</div>
                                            <div className={`${isFullscreen ? 'text-2xl' : 'text-xl'} font-medium`}>
                                                No flights to display
                                            </div>
                                            <div className="text-gray-400 mt-2">
                                                {filter === 'departures' 
                                                    ? 'No departures scheduled'
                                                    : filter === 'arrivals'
                                                    ? 'No arrivals scheduled'
                                                    : 'No flights available'
                                                }
                                            </div>
                                        </td>
                                    </tr>
                                ) : (
                                    filteredFlights.map((flight, index) => (
                                        <tr 
                                            key={flight.id}
                                            className={`border-b border-gray-200 hover:bg-gray-50 transition-colors ${
                                                index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                                            }`}
                                        >
                                            {/* Flight Number */}
                                            <td className={`px-4 py-6 font-mono font-bold ${isFullscreen ? 'text-2xl' : 'text-xl'} text-gray-900`}>
                                                {flight.flight_number}
                                            </td>

                                            {/* Airline */}
                                            <td className={`px-4 py-6 ${isFullscreen ? 'text-lg' : 'text-base'}`}>
                                                <div className="font-medium text-gray-900">{flight.airline.name}</div>
                                                <div className="text-sm text-gray-500">{flight.airline.code}</div>
                                            </td>

                                            {/* Route */}
                                            <td className={`px-4 py-6 ${isFullscreen ? 'text-lg' : 'text-base'}`}>
                                                <div className="flex items-center gap-2">
                                                    <div className="text-center">
                                                        <div className="font-bold text-gray-900">{flight.origin_airport.code}</div>
                                                        <div className="text-xs text-gray-500">{flight.origin_airport.city}</div>
                                                    </div>
                                                    <div className="text-gray-400">→</div>
                                                    <div className="text-center">
                                                        <div className="font-bold text-gray-900">{flight.destination_airport.code}</div>
                                                        <div className="text-xs text-gray-500">{flight.destination_airport.city}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Scheduled Time */}
                                            <td className={`px-4 py-6 text-center font-mono ${isFullscreen ? 'text-xl' : 'text-lg'} text-gray-900`}>
                                                <div>{formatTime(flight.scheduled_departure)}</div>
                                                <div className="text-sm text-gray-500">
                                                    {new Date(flight.scheduled_departure).toLocaleDateString('en-US', { 
                                                        month: 'short', 
                                                        day: 'numeric' 
                                                    })}
                                                </div>
                                            </td>

                                            {/* Actual Time */}
                                            <td className={`px-4 py-6 text-center font-mono ${isFullscreen ? 'text-xl' : 'text-lg'}`}>
                                                {flight.actual_departure ? (
                                                    <div className="text-blue-600 font-bold">
                                                        {formatTime(flight.actual_departure)}
                                                    </div>
                                                ) : (
                                                    <div className="text-gray-400">--:--</div>
                                                )}
                                            </td>

                                            {/* Gate */}
                                            <td className={`px-4 py-6 text-center ${isFullscreen ? 'text-2xl' : 'text-xl'}`}>
                                                {flight.gate ? (
                                                    <div className="inline-flex items-center justify-center w-12 h-12 bg-blue-100 text-blue-800 font-bold rounded-lg">
                                                        {flight.gate}
                                                    </div>
                                                ) : (
                                                    <div className="text-gray-400 text-lg">--</div>
                                                )}
                                            </td>

                                            {/* Status */}
                                            <td className="px-4 py-6 text-center">
                                                <span className={`inline-flex items-center px-4 py-2 rounded-full border font-bold ${isFullscreen ? 'text-lg' : 'text-base'} ${getStatusColor(flight.status)}`}>
                                                    {getStatusText(flight.status)}
                                                </span>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Footer */}
                <div className={`mt-4 text-center text-gray-500 ${isFullscreen ? 'text-sm' : 'text-base'}`}>
                    {lastUpdate && (
                        <p>Last updated: {lastUpdate.toLocaleTimeString()}</p>
                    )}
                    {!kioskMode && (
                        <div className="mt-2 text-xs">
                            <p>Keyboard shortcuts: F11 (Fullscreen) • 1 (All) • 2 (Departures) • 3 (Arrivals) • Space (Toggle Auto-refresh)</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
};

export default DisplayBoard;
import { useEffect, useState, useCallback } from 'react';
import echo from '../echo';

interface FlightStatistics {
    total_flights: number;
    on_time: number;
    delayed: number;
    boarding: number;
    departed: number;
    arrived: number;
    cancelled: number;
    avg_delay_minutes: number;
    last_updated: string;
}

interface AirportStatistics {
    total_departures: number;
    on_time_departures: number;
    delayed_departures: number;
    total_arrivals: number;
    on_time_arrivals: number;
    delayed_arrivals: number;
}

export const useDashboardStatistics = (initialStats?: FlightStatistics) => {
    const [statistics, setStatistics] = useState<FlightStatistics | null>(initialStats || null);
    const [airportStats, setAirportStats] = useState<Record<number, AirportStatistics>>({});
    const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected'>('connecting');
    const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

    // Update statistics
    const updateStatistics = useCallback((newStats: FlightStatistics) => {
        setStatistics(newStats);
        setLastUpdate(new Date());
    }, []);

    // Calculate performance metrics
    const getPerformanceMetrics = useCallback(() => {
        if (!statistics) return null;

        const totalFlights = statistics.total_flights;
        if (totalFlights === 0) return null;

        return {
            onTimePercentage: Math.round((statistics.on_time / totalFlights) * 100),
            delayedPercentage: Math.round((statistics.delayed / totalFlights) * 100),
            cancelledPercentage: Math.round((statistics.cancelled / totalFlights) * 100),
            averageDelay: Math.round(statistics.avg_delay_minutes),
        };
    }, [statistics]);

    useEffect(() => {
        // Listen to dashboard statistics channel
        const dashboardChannel = echo.channel('dashboard');

        // Statistics updated
        dashboardChannel.listen('.statistics.updated', (event: { statistics: FlightStatistics; timestamp: string }) => {
            console.log('Dashboard statistics updated:', event);
            updateStatistics(event.statistics);
        });

        // Connection status listeners
        echo.connector.pusher.connection.bind('connected', () => {
            setConnectionStatus('connected');
            console.log('Dashboard WebSocket connected');
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            setConnectionStatus('disconnected');
            console.log('Dashboard WebSocket disconnected');
        });

        echo.connector.pusher.connection.bind('connecting', () => {
            setConnectionStatus('connecting');
            console.log('Dashboard WebSocket connecting');
        });

        // Cleanup
        return () => {
            echo.leaveChannel('dashboard');
        };
    }, [updateStatistics]);

    // Listen to airport-specific statistics
    const listenToAirportStats = useCallback((airportId: number) => {
        const airportChannel = echo.channel(`airport.${airportId}.stats`);
        
        airportChannel.listen('.airport.statistics.updated', (event: { 
            airport_id: number; 
            statistics: AirportStatistics; 
            timestamp: string; 
        }) => {
            setAirportStats(prev => ({
                ...prev,
                [event.airport_id]: event.statistics
            }));
            setLastUpdate(new Date());
        });

        return () => echo.leaveChannel(`airport.${airportId}.stats`);
    }, []);

    return {
        statistics,
        airportStats,
        connectionStatus,
        lastUpdate,
        performanceMetrics: getPerformanceMetrics(),
        listenToAirportStats,
        setStatistics,
    };
};

export default useDashboardStatistics;
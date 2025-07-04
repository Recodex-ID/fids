import { useEffect, useState, useCallback } from 'react';
import echo from '../echo';

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
    gate: string | null;
    aircraft_type: string | null;
}

interface FlightUpdate {
    flight: Flight;
    old_status?: string;
    timestamp: string;
}

interface FlightDelayUpdate {
    flight: Flight;
    delay_minutes: number;
    reason: string;
    timestamp: string;
}

interface FlightGateUpdate {
    flight: Flight;
    old_gate: string | null;
    new_gate: string | null;
    timestamp: string;
}

export const useRealTimeFlights = (initialFlights: Flight[] = []) => {
    const [flights, setFlights] = useState<Flight[]>(initialFlights);
    const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected'>('connecting');
    const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

    // Update a flight in the list
    const updateFlight = useCallback((updatedFlight: Flight) => {
        setFlights(prevFlights => 
            prevFlights.map(flight => 
                flight.id === updatedFlight.id ? { ...flight, ...updatedFlight } : flight
            )
        );
        setLastUpdate(new Date());
    }, []);

    useEffect(() => {
        // Listen to global flights channel
        const flightsChannel = echo.channel('flights');

        // Flight status changed
        flightsChannel.listen('.flight.status.changed', (event: FlightUpdate) => {
            console.log('Flight status changed:', event);
            updateFlight(event.flight);
        });

        // Flight delayed
        flightsChannel.listen('.flight.delayed', (event: FlightDelayUpdate) => {
            console.log('Flight delayed:', event);
            updateFlight(event.flight);
        });

        // Gate changed
        flightsChannel.listen('.flight.gate.changed', (event: FlightGateUpdate) => {
            console.log('Flight gate changed:', event);
            updateFlight(event.flight);
        });

        // Flight boarding
        flightsChannel.listen('.flight.boarding', (event: FlightUpdate) => {
            console.log('Flight boarding:', event);
            updateFlight(event.flight);
        });

        // Connection status listeners
        echo.connector.pusher.connection.bind('connected', () => {
            setConnectionStatus('connected');
            console.log('WebSocket connected');
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            setConnectionStatus('disconnected');
            console.log('WebSocket disconnected');
        });

        echo.connector.pusher.connection.bind('connecting', () => {
            setConnectionStatus('connecting');
            console.log('WebSocket connecting');
        });

        // Cleanup
        return () => {
            echo.leaveChannel('flights');
        };
    }, [updateFlight]);

    // Listen to airport-specific updates
    const listenToAirport = useCallback((airportId: number) => {
        const airportChannel = echo.channel(`airport.${airportId}`);
        
        airportChannel.listen('.flight.status.changed', (event: FlightUpdate) => {
            updateFlight(event.flight);
        });

        airportChannel.listen('.flight.delayed', (event: FlightDelayUpdate) => {
            updateFlight(event.flight);
        });

        airportChannel.listen('.flight.gate.changed', (event: FlightGateUpdate) => {
            updateFlight(event.flight);
        });

        return () => echo.leaveChannel(`airport.${airportId}`);
    }, [updateFlight]);

    // Listen to specific flight updates
    const listenToFlight = useCallback((flightId: number) => {
        const flightChannel = echo.channel(`flight.${flightId}`);
        
        flightChannel.listen('.flight.status.changed', (event: FlightUpdate) => {
            updateFlight(event.flight);
        });

        flightChannel.listen('.flight.delayed', (event: FlightDelayUpdate) => {
            updateFlight(event.flight);
        });

        flightChannel.listen('.flight.gate.changed', (event: FlightGateUpdate) => {
            updateFlight(event.flight);
        });

        flightChannel.listen('.flight.boarding', (event: FlightUpdate) => {
            updateFlight(event.flight);
        });

        return () => echo.leaveChannel(`flight.${flightId}`);
    }, [updateFlight]);

    return {
        flights,
        connectionStatus,
        lastUpdate,
        listenToAirport,
        listenToFlight,
        setFlights,
    };
};

export default useRealTimeFlights;
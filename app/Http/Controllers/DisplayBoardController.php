<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayBoardController extends Controller
{
    /**
     * Display the main FIDS board
     */
    public function index(Request $request)
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->whereIn('status', ['on_time', 'delayed', 'boarding', 'departed', 'arrived', 'cancelled', 'check_in'])
            ->where('scheduled_departure', '>=', now()->subHours(2))
            ->where('scheduled_departure', '<=', now()->addHours(12))
            ->orderBy('scheduled_departure')
            ->get();

        $kioskMode = $request->boolean('kiosk', false);
        $refreshInterval = $request->integer('refresh', 30000);

        return Inertia::render('DisplayBoard', [
            'initialFlights' => $flights,
            'refreshInterval' => $refreshInterval,
            'kioskMode' => $kioskMode,
        ]);
    }

    /**
     * API endpoint for refreshing flight data
     */
    public function refresh()
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->whereIn('status', ['on_time', 'delayed', 'boarding', 'departed', 'arrived', 'cancelled', 'check_in'])
            ->where('scheduled_departure', '>=', now()->subHours(2))
            ->where('scheduled_departure', '<=', now()->addHours(12))
            ->orderBy('scheduled_departure')
            ->get();

        return response()->json([
            'flights' => $flights,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Display board for departures only
     */
    public function departures(Request $request)
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->whereIn('status', ['on_time', 'delayed', 'boarding', 'check_in'])
            ->where('scheduled_departure', '>=', now())
            ->where('scheduled_departure', '<=', now()->addHours(12))
            ->orderBy('scheduled_departure')
            ->get();

        $kioskMode = $request->boolean('kiosk', false);
        $refreshInterval = $request->integer('refresh', 30000);

        return Inertia::render('DisplayBoard', [
            'initialFlights' => $flights,
            'refreshInterval' => $refreshInterval,
            'kioskMode' => $kioskMode,
            'defaultFilter' => 'departures',
        ]);
    }

    /**
     * Display board for arrivals only
     */
    public function arrivals(Request $request)
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->whereIn('status', ['on_time', 'delayed', 'arrived'])
            ->where('scheduled_arrival', '>=', now()->subHours(2))
            ->where('scheduled_arrival', '<=', now()->addHours(6))
            ->orderBy('scheduled_arrival')
            ->get();

        $kioskMode = $request->boolean('kiosk', false);
        $refreshInterval = $request->integer('refresh', 30000);

        return Inertia::render('DisplayBoard', [
            'initialFlights' => $flights,
            'refreshInterval' => $refreshInterval,
            'kioskMode' => $kioskMode,
            'defaultFilter' => 'arrivals',
        ]);
    }

    /**
     * Kiosk mode display (fullscreen, no controls)
     */
    public function kiosk(Request $request)
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->whereIn('status', ['on_time', 'delayed', 'boarding', 'departed', 'arrived', 'cancelled', 'check_in'])
            ->where('scheduled_departure', '>=', now()->subHours(2))
            ->where('scheduled_departure', '<=', now()->addHours(12))
            ->orderBy('scheduled_departure')
            ->get();

        $refreshInterval = $request->integer('refresh', 30000);

        return Inertia::render('DisplayBoard', [
            'initialFlights' => $flights,
            'refreshInterval' => $refreshInterval,
            'kioskMode' => true,
        ]);
    }

    /**
     * Get flight statistics for display
     */
    public function statistics()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        $stats = [
            'total_flights_today' => Flight::whereBetween('scheduled_departure', [$today, $tomorrow])->count(),
            'on_time' => Flight::where('status', 'on_time')->whereBetween('scheduled_departure', [$today, $tomorrow])->count(),
            'delayed' => Flight::where('status', 'delayed')->whereBetween('scheduled_departure', [$today, $tomorrow])->count(),
            'cancelled' => Flight::where('status', 'cancelled')->whereBetween('scheduled_departure', [$today, $tomorrow])->count(),
            'boarding' => Flight::where('status', 'boarding')->count(),
            'departed' => Flight::where('status', 'departed')->whereBetween('scheduled_departure', [$today, $tomorrow])->count(),
            'arrived' => Flight::where('status', 'arrived')->whereBetween('scheduled_arrival', [$today, $tomorrow])->count(),
        ];

        return response()->json($stats);
    }
}

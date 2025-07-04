<?php

namespace App\Http\Controllers;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        
        // Get dashboard statistics
        $stats = [
            'total_flights' => Flight::count(),
            'flights_today' => Flight::today()->count(),
            'delayed_flights' => Flight::delayed()->count(),
            'total_passengers' => Passenger::count(),
            'checked_in_passengers' => Passenger::checkedIn()->count(),
            'total_airlines' => Airline::count(),
            'total_airports' => Airport::count(),
        ];

        // Get recent flights for today
        $todaysFlights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->today()
            ->orderBy('scheduled_departure')
            ->limit(10)
            ->get();

        // Get delayed flights
        $delayedFlights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->delayed()
            ->orderBy('scheduled_departure')
            ->limit(5)
            ->get();

        // Get boarding flights
        $boardingFlights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->boarding()
            ->orderBy('scheduled_departure')
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'stats' => $stats,
            'todaysFlights' => $todaysFlights,
            'delayedFlights' => $delayedFlights,
            'boardingFlights' => $boardingFlights,
            'userRole' => $user->role ?? 'passenger',
        ]);
    }
}

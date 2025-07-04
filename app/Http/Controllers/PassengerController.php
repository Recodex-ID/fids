<?php

namespace App\Http\Controllers;

use App\Http\Requests\PassengerRequest;
use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class PassengerController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'role:admin,staff']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Passenger::with(['flight.airline', 'flight.originAirport', 'flight.destinationAirport']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('booking_reference', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('flight')) {
            $query->where('flight_id', $request->flight);
        }

        if ($request->filled('checked_in')) {
            if ($request->checked_in === 'yes') {
                $query->whereNotNull('seat_number');
            } else {
                $query->whereNull('seat_number');
            }
        }

        $passengers = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('passengers/index', [
            'passengers' => $passengers,
            'filters' => $request->only(['search', 'flight', 'checked_in']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->active()
            ->orderBy('scheduled_departure')
            ->get();

        return Inertia::render('passengers/create', [
            'flights' => $flights,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PassengerRequest $request): RedirectResponse
    {
        try {
            Passenger::create($request->validated());

            return redirect()->route('passengers.index')
                ->with('success', 'Passenger created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create passenger: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Passenger $passenger): Response
    {
        $passenger->load(['flight.airline', 'flight.originAirport', 'flight.destinationAirport', 'notifications']);

        return Inertia::render('passengers/show', [
            'passenger' => $passenger,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Passenger $passenger): Response
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->active()
            ->orderBy('scheduled_departure')
            ->get();

        return Inertia::render('passengers/edit', [
            'passenger' => $passenger,
            'flights' => $flights,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PassengerRequest $request, Passenger $passenger): RedirectResponse
    {
        try {
            $passenger->update($request->validated());

            return redirect()->route('passengers.index')
                ->with('success', 'Passenger updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update passenger: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passenger $passenger): RedirectResponse
    {
        try {
            $passenger->delete();

            return redirect()->route('passengers.index')
                ->with('success', 'Passenger deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete passenger: ' . $e->getMessage()]);
        }
    }

    /**
     * Check in passenger (assign seat)
     */
    public function checkIn(Request $request, Passenger $passenger): RedirectResponse
    {
        $request->validate([
            'seat_number' => ['required', 'string', 'max:10'],
        ]);

        try {
            $passenger->update(['seat_number' => $request->seat_number]);

            return back()->with('success', 'Passenger checked in successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to check in passenger: ' . $e->getMessage()]);
        }
    }

    /**
     * Search passengers by booking reference
     */
    public function search(Request $request): Response
    {
        $request->validate([
            'booking_reference' => ['required', 'string'],
        ]);

        $passenger = Passenger::with(['flight.airline', 'flight.originAirport', 'flight.destinationAirport'])
            ->where('booking_reference', strtoupper($request->booking_reference))
            ->first();

        return Inertia::render('passengers/search', [
            'passenger' => $passenger,
            'booking_reference' => $request->booking_reference,
        ]);
    }
}

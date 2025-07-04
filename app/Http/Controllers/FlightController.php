<?php

namespace App\Http\Controllers;

use App\Http\Requests\FlightRequest;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class FlightController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'role:admin,staff'], except: ['index', 'show']),
            new Middleware('auth', only: ['index', 'show']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->latest('scheduled_departure');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('scheduled_departure', $request->date);
        }

        if ($request->filled('airline')) {
            $query->where('airline_id', $request->airline);
        }

        if ($request->filled('search')) {
            $query->where('flight_number', 'like', '%' . $request->search . '%');
        }

        $flights = $query->paginate(20)->withQueryString();

        return Inertia::render('flights/index', [
            'flights' => $flights,
            'filters' => $request->only(['status', 'date', 'airline', 'search']),
            'airlines' => Airline::select('id', 'name', 'code')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('flights/create', [
            'airlines' => Airline::select('id', 'name', 'code')->get(),
            'airports' => Airport::select('id', 'name', 'code', 'city')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FlightRequest $request): RedirectResponse
    {
        try {
            Flight::create($request->validated());

            return redirect()->route('flights.index')
                ->with('success', 'Flight created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create flight: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Flight $flight): Response
    {
        $flight->load(['airline', 'originAirport', 'destinationAirport', 'passengers', 'notifications']);

        return Inertia::render('flights/show', [
            'flight' => $flight,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Flight $flight): Response
    {
        return Inertia::render('flights/edit', [
            'flight' => $flight,
            'airlines' => Airline::select('id', 'name', 'code')->get(),
            'airports' => Airport::select('id', 'name', 'code', 'city')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FlightRequest $request, Flight $flight): RedirectResponse
    {
        try {
            $flight->update($request->validated());

            return redirect()->route('flights.index')
                ->with('success', 'Flight updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update flight: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Flight $flight): RedirectResponse
    {
        try {
            $flight->delete();

            return redirect()->route('flights.index')
                ->with('success', 'Flight deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete flight: ' . $e->getMessage()]);
        }
    }

    /**
     * Get today's flights for dashboard
     */
    public function today(): Response
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->today()
            ->orderBy('scheduled_departure')
            ->get();

        return Inertia::render('flights/today', [
            'flights' => $flights,
        ]);
    }

    /**
     * Get delayed flights
     */
    public function delayed(): Response
    {
        $flights = Flight::with(['airline', 'originAirport', 'destinationAirport'])
            ->delayed()
            ->orderBy('scheduled_departure')
            ->get();

        return Inertia::render('flights/delayed', [
            'flights' => $flights,
        ]);
    }
}

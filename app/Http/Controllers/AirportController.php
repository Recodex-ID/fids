<?php

namespace App\Http\Controllers;

use App\Http\Requests\AirportRequest;
use App\Models\Airport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AirportController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'role:admin']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Airport::withCount(['departures', 'arrivals']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $airports = $query->orderBy('name')->paginate(15)->withQueryString();

        $countries = Airport::select('country')->distinct()->orderBy('country')->pluck('country');

        return Inertia::render('airports/index', [
            'airports' => $airports,
            'filters' => $request->only(['search', 'country']),
            'countries' => $countries,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('airports/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AirportRequest $request): RedirectResponse
    {
        try {
            Airport::create($request->validated());

            return redirect()->route('airports.index')
                ->with('success', 'Airport created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create airport: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Airport $airport): Response
    {
        $airport->load(['departures.airline', 'arrivals.airline']);

        return Inertia::render('airports/show', [
            'airport' => $airport,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Airport $airport): Response
    {
        return Inertia::render('airports/edit', [
            'airport' => $airport,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AirportRequest $request, Airport $airport): RedirectResponse
    {
        try {
            $airport->update($request->validated());

            return redirect()->route('airports.index')
                ->with('success', 'Airport updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update airport: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Airport $airport): RedirectResponse
    {
        try {
            $flightCount = $airport->departures()->count() + $airport->arrivals()->count();

            if ($flightCount > 0) {
                return back()->withErrors([
                    'error' => 'Cannot delete airport with existing flights. Please reassign or delete flights first.'
                ]);
            }

            $airport->delete();

            return redirect()->route('airports.index')
                ->with('success', 'Airport deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete airport: ' . $e->getMessage()]);
        }
    }
}

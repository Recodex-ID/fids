<?php

namespace App\Http\Controllers;

use App\Http\Requests\AirlineRequest;
use App\Models\Airline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class AirlineController extends Controller implements HasMiddleware
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
        $query = Airline::withCount('flights');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $airlines = $query->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('airlines/index', [
            'airlines' => $airlines,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('airlines/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AirlineRequest $request): RedirectResponse
    {
        try {
            Airline::create($request->validated());

            return redirect()->route('airlines.index')
                ->with('success', 'Airline created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create airline: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Airline $airline): Response
    {
        $airline->load('flights.originAirport', 'flights.destinationAirport');

        return Inertia::render('airlines/show', [
            'airline' => $airline,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Airline $airline): Response
    {
        return Inertia::render('airlines/edit', [
            'airline' => $airline,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AirlineRequest $request, Airline $airline): RedirectResponse
    {
        try {
            $airline->update($request->validated());

            return redirect()->route('airlines.index')
                ->with('success', 'Airline updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update airline: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Airline $airline): RedirectResponse
    {
        try {
            if ($airline->flights()->exists()) {
                return back()->withErrors([
                    'error' => 'Cannot delete airline with existing flights. Please reassign or delete flights first.'
                ]);
            }

            $airline->delete();

            return redirect()->route('airlines.index')
                ->with('success', 'Airline deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete airline: ' . $e->getMessage()]);
        }
    }
}

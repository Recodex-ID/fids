<?php

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\AirportController;
use App\Http\Controllers\DisplayBoardController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\PassengerController;
use Illuminate\Support\Facades\Route;

// FIDS Routes - All routes require authentication
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Flight Management Routes
    Route::resource('flights', FlightController::class);
    Route::get('flights-today', [FlightController::class, 'today'])->name('flights.today');
    Route::get('flights-delayed', [FlightController::class, 'delayed'])->name('flights.delayed');
    
    // Airline Management Routes (Admin only)
    Route::resource('airlines', AirlineController::class);
    
    // Airport Management Routes (Admin only)
    Route::resource('airports', AirportController::class);
    
    // Passenger Management Routes (Admin/Staff only)
    Route::resource('passengers', PassengerController::class);
    Route::post('passengers/{passenger}/check-in', [PassengerController::class, 'checkIn'])->name('passengers.check-in');
    Route::get('passenger-search', [PassengerController::class, 'search'])->name('passengers.search');
});

// Public flight information (no auth required)
Route::get('departures', [FlightController::class, 'index'])->name('public.departures');
Route::get('arrivals', [FlightController::class, 'index'])->name('public.arrivals');
Route::get('flight/{flight}', [FlightController::class, 'show'])->name('public.flight');

// Display Board Routes (public access)
Route::get('display-board', [DisplayBoardController::class, 'index'])->name('display-board');
Route::get('display-board/departures', [DisplayBoardController::class, 'departures'])->name('display-board.departures');
Route::get('display-board/arrivals', [DisplayBoardController::class, 'arrivals'])->name('display-board.arrivals');
Route::get('display-board/kiosk', [DisplayBoardController::class, 'kiosk'])->name('display-board.kiosk');

// Display Board API Routes (public access)
Route::get('api/display-board/refresh', [DisplayBoardController::class, 'refresh'])->name('api.display-board.refresh');
Route::get('api/display-board/statistics', [DisplayBoardController::class, 'statistics'])->name('api.display-board.statistics');
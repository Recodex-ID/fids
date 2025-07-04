<?php

namespace App\Providers;

use App\Models\Flight;
use App\Observers\FlightObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Flight::observe(FlightObserver::class);
    }
}

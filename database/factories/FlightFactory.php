<?php

namespace Database\Factories;

use App\Models\Airline;
use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Flight>
 */
class FlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departureTime = fake()->dateTimeBetween('now', '+7 days');
        $arrivalTime = fake()->dateTimeBetween($departureTime, $departureTime->format('Y-m-d H:i:s') . ' +12 hours');
        
        $aircraftTypes = ['Boeing 737', 'Airbus A320', 'Boeing 777', 'Airbus A330', 'Boeing 787', 'Airbus A350'];
        $gates = ['A' . fake()->numberBetween(1, 30), 'B' . fake()->numberBetween(1, 30), 'C' . fake()->numberBetween(1, 30)];
        
        return [
            'flight_number' => fake()->randomElement(['AA', 'DL', 'UA', 'WN', 'B6']) . fake()->numberBetween(1000, 9999),
            'airline_id' => Airline::factory(),
            'origin_airport_id' => Airport::factory(),
            'destination_airport_id' => Airport::factory(),
            'scheduled_departure' => $departureTime,
            'scheduled_arrival' => $arrivalTime,
            'actual_departure' => null,
            'actual_arrival' => null,
            'gate' => fake()->randomElement($gates),
            'status' => fake()->randomElement(['scheduled', 'boarding', 'departed', 'delayed', 'cancelled', 'arrived']),
            'aircraft_type' => fake()->randomElement($aircraftTypes),
        ];
    }
    
    public function delayed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delayed',
            'actual_departure' => fake()->dateTimeBetween($attributes['scheduled_departure'], $attributes['scheduled_departure'] . ' +3 hours'),
        ]);
    }
    
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'actual_departure' => null,
            'actual_arrival' => null,
        ]);
    }
    
    public function departed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'departed',
            'actual_departure' => $attributes['scheduled_departure'],
        ]);
    }
    
    public function boarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'boarding',
        ]);
    }
}

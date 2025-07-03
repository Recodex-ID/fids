<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Airport>
 */
class AirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $airports = [
            ['name' => 'John F. Kennedy International Airport', 'code' => 'JFK', 'city' => 'New York', 'country' => 'USA', 'timezone' => 'America/New_York'],
            ['name' => 'Los Angeles International Airport', 'code' => 'LAX', 'city' => 'Los Angeles', 'country' => 'USA', 'timezone' => 'America/Los_Angeles'],
            ['name' => 'Chicago O\'Hare International Airport', 'code' => 'ORD', 'city' => 'Chicago', 'country' => 'USA', 'timezone' => 'America/Chicago'],
            ['name' => 'Hartsfield-Jackson Atlanta International Airport', 'code' => 'ATL', 'city' => 'Atlanta', 'country' => 'USA', 'timezone' => 'America/New_York'],
            ['name' => 'Denver International Airport', 'code' => 'DEN', 'city' => 'Denver', 'country' => 'USA', 'timezone' => 'America/Denver'],
            ['name' => 'London Heathrow Airport', 'code' => 'LHR', 'city' => 'London', 'country' => 'UK', 'timezone' => 'Europe/London'],
            ['name' => 'Frankfurt Airport', 'code' => 'FRA', 'city' => 'Frankfurt', 'country' => 'Germany', 'timezone' => 'Europe/Berlin'],
            ['name' => 'Toronto Pearson International Airport', 'code' => 'YYZ', 'city' => 'Toronto', 'country' => 'Canada', 'timezone' => 'America/Toronto'],
            ['name' => 'Dubai International Airport', 'code' => 'DXB', 'city' => 'Dubai', 'country' => 'UAE', 'timezone' => 'Asia/Dubai'],
            ['name' => 'Singapore Changi Airport', 'code' => 'SIN', 'city' => 'Singapore', 'country' => 'Singapore', 'timezone' => 'Asia/Singapore'],
        ];

        $airport = fake()->randomElement($airports);
        
        return [
            'name' => $airport['name'],
            'code' => $airport['code'],
            'city' => $airport['city'],
            'country' => $airport['country'],
            'timezone' => $airport['timezone'],
        ];
    }
}

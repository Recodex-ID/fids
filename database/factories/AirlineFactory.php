<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Airline>
 */
class AirlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $airlines = [
            ['name' => 'American Airlines', 'code' => 'AA'],
            ['name' => 'Delta Air Lines', 'code' => 'DL'],
            ['name' => 'United Airlines', 'code' => 'UA'],
            ['name' => 'Southwest Airlines', 'code' => 'WN'],
            ['name' => 'JetBlue Airways', 'code' => 'B6'],
            ['name' => 'Alaska Airlines', 'code' => 'AS'],
            ['name' => 'British Airways', 'code' => 'BA'],
            ['name' => 'Lufthansa', 'code' => 'LH'],
            ['name' => 'Air Canada', 'code' => 'AC'],
            ['name' => 'Emirates', 'code' => 'EK'],
            ['name' => 'Qatar Airways', 'code' => 'QR'],
            ['name' => 'Singapore Airlines', 'code' => 'SQ'],
        ];

        $airline = fake()->randomElement($airlines);
        
        return [
            'name' => $airline['name'],
            'code' => $airline['code'],
            'logo_url' => 'https://example.com/logos/' . strtolower($airline['code']) . '.png',
        ];
    }
}

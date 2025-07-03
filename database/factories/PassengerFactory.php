<?php

namespace Database\Factories;

use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Passenger>
 */
class PassengerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $seatRows = fake()->numberBetween(1, 35);
        $seatLetters = ['A', 'B', 'C', 'D', 'E', 'F'];
        
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'flight_id' => Flight::factory(),
            'seat_number' => $seatRows . fake()->randomElement($seatLetters),
            'booking_reference' => strtoupper(Str::random(6)),
        ];
    }
    
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'seat_number' => fake()->numberBetween(1, 35) . fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
        ]);
    }
    
    public function withoutSeat(): static
    {
        return $this->state(fn (array $attributes) => [
            'seat_number' => null,
        ]);
    }
}

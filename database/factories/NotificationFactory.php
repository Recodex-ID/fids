<?php

namespace Database\Factories;

use App\Models\Flight;
use App\Models\Passenger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['delay', 'gate_change', 'boarding', 'cancellation', 'arrival', 'departure'];
        $type = fake()->randomElement($types);
        
        $messages = [
            'delay' => 'Your flight has been delayed by ' . fake()->numberBetween(30, 180) . ' minutes.',
            'gate_change' => 'Gate change: Your flight has moved to gate ' . fake()->randomElement(['A', 'B', 'C']) . fake()->numberBetween(1, 30) . '.',
            'boarding' => 'Your flight is now boarding at gate ' . fake()->randomElement(['A', 'B', 'C']) . fake()->numberBetween(1, 30) . '.',
            'cancellation' => 'Your flight has been cancelled. Please contact customer service.',
            'arrival' => 'Your flight has arrived at the destination.',
            'departure' => 'Your flight has departed on time.',
        ];
        
        return [
            'passenger_id' => Passenger::factory(),
            'flight_id' => Flight::factory(),
            'type' => $type,
            'message' => $messages[$type],
            'sent_at' => fake()->boolean(70) ? fake()->dateTimeThisMonth() : null,
        ];
    }
    
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => fake()->dateTimeThisMonth(),
        ]);
    }
    
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => null,
        ]);
    }
    
    public function delay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'delay',
            'message' => 'Your flight has been delayed by ' . fake()->numberBetween(30, 180) . ' minutes.',
        ]);
    }
    
    public function gateChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'gate_change',
            'message' => 'Gate change: Your flight has moved to gate ' . fake()->randomElement(['A', 'B', 'C']) . fake()->numberBetween(1, 30) . '.',
        ]);
    }
}

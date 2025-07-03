<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $flights = [
            [
                'flight_number' => 'AA1001',
                'airline_id' => 1,
                'origin_airport_id' => 1,
                'destination_airport_id' => 2,
                'scheduled_departure' => $now->copy()->addHours(2),
                'scheduled_arrival' => $now->copy()->addHours(8),
                'gate' => 'A12',
                'status' => 'scheduled',
                'aircraft_type' => 'Boeing 737'
            ],
            [
                'flight_number' => 'DL2002',
                'airline_id' => 2,
                'origin_airport_id' => 3,
                'destination_airport_id' => 4,
                'scheduled_departure' => $now->copy()->addHours(3),
                'scheduled_arrival' => $now->copy()->addHours(5),
                'gate' => 'B15',
                'status' => 'boarding',
                'aircraft_type' => 'Airbus A320'
            ],
            [
                'flight_number' => 'UA3003',
                'airline_id' => 3,
                'origin_airport_id' => 5,
                'destination_airport_id' => 6,
                'scheduled_departure' => $now->copy()->addHours(4),
                'scheduled_arrival' => $now->copy()->addHours(12),
                'gate' => 'C20',
                'status' => 'delayed',
                'aircraft_type' => 'Boeing 777'
            ],
            [
                'flight_number' => 'WN4004',
                'airline_id' => 4,
                'origin_airport_id' => 7,
                'destination_airport_id' => 8,
                'scheduled_departure' => $now->copy()->addHours(1),
                'scheduled_arrival' => $now->copy()->addHours(6),
                'gate' => 'D10',
                'status' => 'departed',
                'aircraft_type' => 'Boeing 737'
            ],
            [
                'flight_number' => 'B65005',
                'airline_id' => 5,
                'origin_airport_id' => 9,
                'destination_airport_id' => 10,
                'scheduled_departure' => $now->copy()->addHours(6),
                'scheduled_arrival' => $now->copy()->addHours(9),
                'gate' => 'E25',
                'status' => 'scheduled',
                'aircraft_type' => 'Airbus A321'
            ],
        ];

        foreach ($flights as $flight) {
            DB::table('flights')->insert([
                'flight_number' => $flight['flight_number'],
                'airline_id' => $flight['airline_id'],
                'origin_airport_id' => $flight['origin_airport_id'],
                'destination_airport_id' => $flight['destination_airport_id'],
                'scheduled_departure' => $flight['scheduled_departure'],
                'scheduled_arrival' => $flight['scheduled_arrival'],
                'gate' => $flight['gate'],
                'status' => $flight['status'],
                'aircraft_type' => $flight['aircraft_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

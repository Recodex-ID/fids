<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PassengerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $passengers = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+1234567890',
                'flight_id' => 1,
                'seat_number' => '12A',
                'booking_reference' => Str::random(6)
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@example.com',
                'phone' => '+1234567891',
                'flight_id' => 1,
                'seat_number' => '12B',
                'booking_reference' => Str::random(6)
            ],
            [
                'name' => 'Michael Johnson',
                'email' => 'michael.johnson@example.com',
                'phone' => '+1234567892',
                'flight_id' => 2,
                'seat_number' => '8C',
                'booking_reference' => Str::random(6)
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah.wilson@example.com',
                'phone' => '+1234567893',
                'flight_id' => 3,
                'seat_number' => '15F',
                'booking_reference' => Str::random(6)
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.brown@example.com',
                'phone' => '+1234567894',
                'flight_id' => 4,
                'seat_number' => '22D',
                'booking_reference' => Str::random(6)
            ],
            [
                'name' => 'Lisa Davis',
                'email' => 'lisa.davis@example.com',
                'phone' => '+1234567895',
                'flight_id' => 5,
                'seat_number' => '5A',
                'booking_reference' => Str::random(6)
            ],
        ];

        foreach ($passengers as $passenger) {
            DB::table('passengers')->insert([
                'name' => $passenger['name'],
                'email' => $passenger['email'],
                'phone' => $passenger['phone'],
                'flight_id' => $passenger['flight_id'],
                'seat_number' => $passenger['seat_number'],
                'booking_reference' => $passenger['booking_reference'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

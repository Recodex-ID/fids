<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $notifications = [
            [
                'passenger_id' => 1,
                'flight_id' => 1,
                'type' => 'boarding',
                'message' => 'Your flight AA1001 is now boarding at gate A12.',
                'sent_at' => now()->subMinutes(30)
            ],
            [
                'passenger_id' => 2,
                'flight_id' => 1,
                'type' => 'gate_change',
                'message' => 'Gate change: Your flight AA1001 has moved to gate A15.',
                'sent_at' => now()->subHours(1)
            ],
            [
                'passenger_id' => 3,
                'flight_id' => 2,
                'type' => 'delay',
                'message' => 'Your flight DL2002 is delayed by 45 minutes.',
                'sent_at' => now()->subHours(2)
            ],
            [
                'passenger_id' => 4,
                'flight_id' => 3,
                'type' => 'delay',
                'message' => 'Your flight UA3003 is delayed due to weather conditions.',
                'sent_at' => now()->subMinutes(45)
            ],
            [
                'passenger_id' => 5,
                'flight_id' => 4,
                'type' => 'departure',
                'message' => 'Your flight WN4004 has departed on time.',
                'sent_at' => now()->subMinutes(15)
            ],
            [
                'passenger_id' => 6,
                'flight_id' => 5,
                'type' => 'boarding',
                'message' => 'Boarding will begin in 30 minutes for flight B65005.',
                'sent_at' => now()->subMinutes(10)
            ],
        ];

        foreach ($notifications as $notification) {
            DB::table('notifications')->insert([
                'passenger_id' => $notification['passenger_id'],
                'flight_id' => $notification['flight_id'],
                'type' => $notification['type'],
                'message' => $notification['message'],
                'sent_at' => $notification['sent_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

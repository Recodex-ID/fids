<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
            ['name' => 'Miami International Airport', 'code' => 'MIA', 'city' => 'Miami', 'country' => 'USA', 'timezone' => 'America/New_York'],
        ];

        foreach ($airports as $airport) {
            DB::table('airports')->insert([
                'name' => $airport['name'],
                'code' => $airport['code'],
                'city' => $airport['city'],
                'country' => $airport['country'],
                'timezone' => $airport['timezone'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

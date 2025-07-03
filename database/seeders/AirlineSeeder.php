<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirlineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $airlines = [
            ['name' => 'American Airlines', 'code' => 'AA', 'logo_url' => 'https://example.com/logos/aa.png'],
            ['name' => 'Delta Air Lines', 'code' => 'DL', 'logo_url' => 'https://example.com/logos/dl.png'],
            ['name' => 'United Airlines', 'code' => 'UA', 'logo_url' => 'https://example.com/logos/ua.png'],
            ['name' => 'Southwest Airlines', 'code' => 'WN', 'logo_url' => 'https://example.com/logos/wn.png'],
            ['name' => 'JetBlue Airways', 'code' => 'B6', 'logo_url' => 'https://example.com/logos/b6.png'],
            ['name' => 'Alaska Airlines', 'code' => 'AS', 'logo_url' => 'https://example.com/logos/as.png'],
            ['name' => 'British Airways', 'code' => 'BA', 'logo_url' => 'https://example.com/logos/ba.png'],
            ['name' => 'Lufthansa', 'code' => 'LH', 'logo_url' => 'https://example.com/logos/lh.png'],
            ['name' => 'Air Canada', 'code' => 'AC', 'logo_url' => 'https://example.com/logos/ac.png'],
            ['name' => 'Emirates', 'code' => 'EK', 'logo_url' => 'https://example.com/logos/ek.png'],
        ];

        foreach ($airlines as $airline) {
            DB::table('airlines')->insert([
                'name' => $airline['name'],
                'code' => $airline['code'],
                'logo_url' => $airline['logo_url'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

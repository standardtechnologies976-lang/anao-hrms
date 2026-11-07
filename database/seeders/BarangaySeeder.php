<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Baguindoc', 'Bantog', 'Campos', 'Carmen', 'Casili', 'Don Ramon',
            'Hernando', 'Poblacion', 'Rizal', 'San Francisco East',
            'San Francisco West', 'San Jose North', 'San Jose South', 'San Juan',
            'San Roque', 'Santo Domingo', 'Sinense', 'Suaverdez',
        ];

        foreach ($barangays as $name) {
            Barangay::firstOrCreate(['name' => $name]);
        }
    }
}

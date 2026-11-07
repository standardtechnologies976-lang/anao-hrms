<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class WardSeeder extends Seeder
{
    public function run(): void
    {
        $wards = [
            ['name' => 'Lying-in / Birthing', 'type' => 'Maternity', 'beds' => 4],
            ['name' => 'Observation Ward', 'type' => 'General', 'beds' => 6],
            ['name' => 'Isolation Room', 'type' => 'Isolation', 'beds' => 2],
        ];

        foreach ($wards as $w) {
            $ward = Ward::firstOrCreate(['name' => $w['name']], ['type' => $w['type'], 'active' => true]);
            for ($i = 1; $i <= $w['beds']; $i++) {
                Bed::firstOrCreate(['ward_id' => $ward->id, 'label' => 'Bed '.$i]);
            }
        }
    }
}

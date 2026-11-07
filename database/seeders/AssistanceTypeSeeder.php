<?php

namespace Database\Seeders;

use App\Models\AssistanceType;
use Illuminate\Database\Seeder;

class AssistanceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['Medical Assistance', 'AICS'],
            ['Burial Assistance', 'AICS'],
            ['Financial Assistance', 'AICS'],
            ['Educational Assistance', 'AICS'],
            ['Food Assistance / Food Pack', 'AICS'],
            ['Transportation Assistance', 'AICS'],
            ['Livelihood Assistance', 'Livelihood'],
            ['Calamity / Disaster Assistance', 'Calamity'],
            ['Solo Parent Assistance', 'Sectoral'],
            ['Senior Citizen Assistance', 'Sectoral'],
            ['PWD Assistance', 'Sectoral'],
        ];

        foreach ($types as [$name, $category]) {
            AssistanceType::firstOrCreate(['name' => $name], ['category' => $category, 'active' => true]);
        }
    }
}

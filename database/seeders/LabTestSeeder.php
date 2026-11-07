<?php

namespace Database\Seeders;

use App\Models\LabTest;
use Illuminate\Database\Seeder;

class LabTestSeeder extends Seeder
{
    public function run(): void
    {
        $tests = [
            ['Complete Blood Count (CBC)', 'Hematology', null, null],
            ['Hemoglobin', 'Hematology', 'g/dL', 'M 14-18, F 12-16'],
            ['Hematocrit', 'Hematology', '%', 'M 42-52, F 37-47'],
            ['Platelet Count', 'Hematology', 'x10^9/L', '150-450'],
            ['Blood Typing (ABO/Rh)', 'Hematology', null, null],
            ['Urinalysis', 'Clinical Microscopy', null, null],
            ['Fecalysis', 'Clinical Microscopy', null, null],
            ['Pregnancy Test (HCG)', 'Clinical Microscopy', null, null],
            ['Fasting Blood Sugar (FBS)', 'Clinical Chemistry', 'mg/dL', '70-100'],
            ['Random Blood Sugar (RBS)', 'Clinical Chemistry', 'mg/dL', '<140'],
            ['HbA1c', 'Clinical Chemistry', '%', '<5.7'],
            ['Creatinine', 'Clinical Chemistry', 'mg/dL', '0.6-1.3'],
            ['Blood Uric Acid', 'Clinical Chemistry', 'mg/dL', 'M 3.4-7.0, F 2.4-6.0'],
            ['Total Cholesterol', 'Clinical Chemistry', 'mg/dL', '<200'],
            ['Triglycerides', 'Clinical Chemistry', 'mg/dL', '<150'],
            ['SGPT / ALT', 'Clinical Chemistry', 'U/L', '<40'],
            ['SGOT / AST', 'Clinical Chemistry', 'U/L', '<40'],
            ['Hepatitis B Surface Antigen (HBsAg)', 'Serology', null, 'Non-reactive'],
            ['HIV Screening', 'Serology', null, 'Non-reactive'],
            ['VDRL / RPR', 'Serology', null, 'Non-reactive'],
            ['Dengue NS1 Antigen', 'Serology', null, 'Negative'],
            ['Sputum Microscopy (AFB/TB)', 'Microbiology', null, 'Negative'],
        ];

        foreach ($tests as [$name, $category, $unit, $range]) {
            LabTest::firstOrCreate(
                ['name' => $name],
                ['category' => $category, 'unit' => $unit, 'reference_range' => $range, 'active' => true]
            );
        }
    }
}

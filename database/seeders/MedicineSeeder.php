<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $meds = [
            ['Paracetamol', 'Paracetamol', 'Tablet', '500 mg', 'pcs'],
            ['Paracetamol Syrup', 'Paracetamol', 'Syrup', '250 mg/5 ml', 'bottle'],
            ['Amoxicillin', 'Amoxicillin', 'Capsule', '500 mg', 'pcs'],
            ['Amoxicillin Suspension', 'Amoxicillin', 'Suspension', '250 mg/5 ml', 'bottle'],
            ['Cotrimoxazole', 'Sulfamethoxazole + Trimethoprim', 'Tablet', '800/160 mg', 'pcs'],
            ['Metronidazole', 'Metronidazole', 'Tablet', '500 mg', 'pcs'],
            ['Metformin', 'Metformin', 'Tablet', '500 mg', 'pcs'],
            ['Amlodipine', 'Amlodipine', 'Tablet', '5 mg', 'pcs'],
            ['Amlodipine', 'Amlodipine', 'Tablet', '10 mg', 'pcs'],
            ['Losartan', 'Losartan', 'Tablet', '50 mg', 'pcs'],
            ['Salbutamol Nebule', 'Salbutamol', 'Nebule', '2.5 mg', 'neb'],
            ['Cetirizine', 'Cetirizine', 'Tablet', '10 mg', 'pcs'],
            ['Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '500 mg', 'pcs'],
            ['Ferrous Sulfate + Folic Acid', 'Ferrous Sulfate + Folic Acid', 'Tablet', '', 'pcs'],
            ['Vitamin B Complex', 'Vitamin B Complex', 'Tablet', '', 'pcs'],
            ['Ascorbic Acid', 'Ascorbic Acid', 'Tablet', '500 mg', 'pcs'],
            ['Oral Rehydration Salts', 'ORS', 'Sachet', '', 'sachet'],
            ['Omeprazole', 'Omeprazole', 'Capsule', '20 mg', 'pcs'],
        ];

        foreach ($meds as [$name, $generic, $form, $strength, $unit]) {
            Medicine::firstOrCreate(
                ['name' => $name, 'strength' => $strength],
                ['generic_name' => $generic, 'form' => $form, 'unit' => $unit, 'reorder_level' => 50, 'active' => true]
            );
        }
    }
}

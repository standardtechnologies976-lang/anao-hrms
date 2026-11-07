<?php

namespace Database\Seeders;

use App\Models\ImagingExam;
use Illuminate\Database\Seeder;

class ImagingExamSeeder extends Seeder
{
    public function run(): void
    {
        $exams = [
            ['Chest X-ray PA', 'X-ray'],
            ['Chest X-ray PA-Lateral', 'X-ray'],
            ['Abdominal X-ray (Flat/Upright)', 'X-ray'],
            ['Skull X-ray', 'X-ray'],
            ['Cervical Spine X-ray', 'X-ray'],
            ['Upper Extremity X-ray', 'X-ray'],
            ['Lower Extremity X-ray', 'X-ray'],
            ['Pelvis X-ray', 'X-ray'],
            ['Whole Abdomen Ultrasound', 'Ultrasound'],
            ['KUB Ultrasound', 'Ultrasound'],
            ['Pelvic Ultrasound', 'Ultrasound'],
            ['Transvaginal Ultrasound', 'Ultrasound'],
            ['OB / Pregnancy Ultrasound', 'Ultrasound'],
            ['Breast Ultrasound', 'Ultrasound'],
            ['Thyroid Ultrasound', 'Ultrasound'],
            ['12-Lead ECG', 'ECG'],
        ];

        foreach ($exams as [$name, $modality]) {
            ImagingExam::firstOrCreate(['name' => $name], ['modality' => $modality, 'active' => true]);
        }
    }
}

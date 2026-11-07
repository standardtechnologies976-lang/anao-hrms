<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\AssistanceRequest;
use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Bed;
use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\Dispense;
use App\Models\FieldVisit;
use App\Models\Household;
use App\Models\ImagingExam;
use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\RadiologyOrder;
use App\Models\StockMovement;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\TriageAssessment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private array $surnames = ['Dela Cruz','Santos','Reyes','Ramos','Mendoza','Garcia','Cruz','Bautista','Ocampo','Aquino','Villanueva','Castillo','Flores','Rivera','Torres','Gonzales','Domingo','Salvador','Bernardo','Pascual','Manalo','Tolentino','Navarro','Del Rosario','Valdez','Aguilar','Soriano','Marquez','Fernandez','Diaz'];
    private array $male = ['Juan','Jose','Pedro','Ramon','Andres','Emmanuel','Mark','Angelo','Ricardo','Danilo','Rodel','Christian','Michael','Rolando','Ernesto','Noel','Marlon','Joel','Rey','Arnel'];
    private array $female = ['Maria','Ana','Rosa','Josefa','Cristina','Grace','Mae','Angelica','Divina','Luzviminda','Corazon','Elena','Teresa','Jocelyn','Marites','Aileen','Nenita','Liza','Girlie','Rowena'];
    private array $diagnoses = ['Acute Upper Respiratory Infection','Hypertension','Type 2 Diabetes Mellitus','Acute Gastroenteritis','Urinary Tract Infection','Bronchial Asthma','Pneumonia','Dengue Fever','Animal Bite','Tuberculosis','Skin Infection','Anemia','Migraine','Allergic Rhinitis','Osteoarthritis'];

    public function run(): void
    {
        $this->call([BarangaySeeder::class, WardSeeder::class, LabTestSeeder::class, ImagingExamSeeder::class, MedicineSeeder::class, AssistanceTypeSeeder::class]);

        DB::transaction(function () {
            $staff = $this->makeStaff();
            $citizens = $this->makeCitizens(240);
            $this->makeHouseholds($citizens, $staff);
            $this->enrollPortal($citizens, 25);
            $triages = $this->makeTriage($citizens, $staff, 90);
            $this->makeConsultations($citizens, $triages, $staff, 120);
            $this->makeAdmissions($citizens, $staff, 16);
            $this->makeLabOrders($citizens, $staff, 70);
            $this->makeRadiology($citizens, $staff, 40);
            $this->stockAndDispense($citizens, $staff, 60);
            $this->makeAssistance($citizens, $staff, 55);
            $this->makeAppointments($citizens, 40);
            $this->makeFieldVisits($citizens, $staff, 80);
            $this->makeSurveys($citizens, $staff);
        });

        $this->command->info('Demo data seeded.');
    }

    private function d(int $maxDaysAgo): \Carbon\CarbonInterface
    {
        return now()->subDays(random_int(0, $maxDaysAgo))->setTime(random_int(8, 16), Arr::random([0, 15, 30, 45]));
    }

    private function pickName(string $sex): string
    {
        $first = $sex === 'male' ? Arr::random($this->male) : Arr::random($this->female);
        return $first.' '.Arr::random($this->surnames);
    }

    private function makeStaff(): array
    {
        $barangays = Barangay::pluck('id')->all();
        $roles = [
            ['Dr. Elena Reyes', 'physician1@anao.gov.ph', 'physician', 'Municipal Health Officer', ['citizens','triage','physician','nursing','laboratory','radiology','pharmacy','appointments']],
            ['Nurse Grace Santos', 'nurse1@anao.gov.ph', 'nurse', 'Public Health Nurse', ['citizens','triage','nursing','appointments']],
            ['Ana Cruz', 'medtech1@anao.gov.ph', 'lab_tech', 'Medical Technologist', ['citizens','laboratory']],
            ['Mark Flores', 'radtech1@anao.gov.ph', 'rad_tech', 'Radiologic Technologist', ['citizens','radiology']],
            ['Rosa Domingo', 'pharma1@anao.gov.ph', 'pharmacist', 'Pharmacist', ['citizens','pharmacy']],
            ['Ricardo Torres', 'mswd1@anao.gov.ph', 'mswd_officer', 'MSWD Officer', ['citizens','mswd']],
            ['Divina Ramos', 'reception1@anao.gov.ph', 'receptionist', 'Receptionist', ['citizens','triage','appointments']],
        ];
        $staff = ['physicians' => [], 'nurses' => [], 'labtech' => null, 'radtech' => null, 'pharma' => null, 'mswd' => null, 'bhws' => []];
        foreach ($roles as [$name, $email, $role, $pos, $perms]) {
            $u = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'password' => Hash::make('password'), 'role' => $role, 'position' => $pos,
                'is_admin' => false, 'permissions' => $perms,
            ]);
            if ($role === 'physician') $staff['physicians'][] = $u->id;
            if ($role === 'nurse') $staff['nurses'][] = $u->id;
            if ($role === 'lab_tech') $staff['labtech'] = $u->id;
            if ($role === 'rad_tech') $staff['radtech'] = $u->id;
            if ($role === 'pharmacist') $staff['pharma'] = $u->id;
            if ($role === 'mswd_officer') $staff['mswd'] = $u->id;
        }
        foreach (array_slice($barangays, 0, 8) as $i => $bx) {
            $u = User::firstOrCreate(['email' => "bhw{$i}@anao.gov.ph"], [
                'name' => 'BHW '.$this->pickName('female'), 'password' => Hash::make('password'),
                'role' => 'bhw', 'position' => 'Barangay Health Worker', 'barangay_id' => $bx,
                'is_admin' => false, 'permissions' => ['bhw-portal', 'surveys'],
            ]);
            $staff['bhws'][$bx] = $u->id;
        }
        return $staff;
    }

    private function makeCitizens(int $n): array
    {
        $barangays = Barangay::pluck('id')->all();
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $sex = Arr::random(['male', 'female']);
            $age = random_int(1, 88);
            $birth = now()->subYears($age)->subDays(random_int(0, 364));
            $c = Citizen::create([
                'last_name' => Arr::random($this->surnames),
                'first_name' => $sex === 'male' ? Arr::random($this->male) : Arr::random($this->female),
                'middle_name' => Arr::random($this->surnames),
                'sex' => $sex,
                'birth_date' => $birth->toDateString(),
                'civil_status' => $age >= 20 ? Arr::random(['Single','Married','Married','Widowed']) : 'Single',
                'contact_no' => '09'.random_int(100000000, 999999999),
                'barangay_id' => Arr::random($barangays),
                'purok' => 'Purok '.random_int(1, 6),
                'philhealth_no' => random_int(0, 1) ? (string) random_int(100000000000, 999999999999) : null,
                'occupation' => Arr::random(['Farmer','Laborer','Vendor','Teacher','Student','Housekeeper','Driver','None','Self-employed']),
                'is_senior' => $age >= 60,
                'is_pwd' => random_int(1, 20) === 1,
                'is_4ps' => random_int(1, 5) === 1,
                'is_solo_parent' => random_int(1, 15) === 1,
                'is_pregnant' => $sex === 'female' && $age >= 18 && $age <= 44 && random_int(1, 10) === 1,
            ]);
            $ids[] = $c->id;
        }
        return $ids;
    }

    private function makeHouseholds(array $citizens, array $staff): void
    {
        $barangays = Barangay::pluck('id')->all();
        $pool = collect($citizens)->shuffle()->values();
        $idx = 0;
        for ($i = 0; $i < 65 && $idx < $pool->count(); $i++) {
            $bx = Arr::random($barangays);
            $h = Household::create([
                'barangay_id' => $bx,
                'purok' => 'Purok '.random_int(1, 6),
                'address' => random_int(1, 200).' Rizal St.',
                'house_ownership' => Arr::random(['Owned','Rented','Shared']),
                'house_type' => Arr::random(['Concrete','Semi-concrete','Light materials']),
                'water_source' => Arr::random(['Level II','Level III','Deep well']),
                'toilet_facility' => Arr::random(['Water-sealed','Antipolo type']),
                'has_electricity' => random_int(1, 10) > 2,
                'waste_disposal' => Arr::random(['Collected','Composting','Burning']),
                'surveyed_at' => $this->d(120)->toDateString(),
                'surveyed_by' => Arr::random($staff['bhws'] ?: [null]),
            ]);
            $members = random_int(2, 6);
            $head = null;
            for ($m = 0; $m < $members && $idx < $pool->count(); $m++, $idx++) {
                $cid = $pool[$idx];
                $rel = $m === 0 ? 'Head' : Arr::random(['Spouse','Son','Daughter','Parent','Sibling','Relative']);
                Citizen::where('id', $cid)->update(['household_id' => $h->id, 'relationship_to_head' => $rel, 'barangay_id' => $bx]);
                if ($m === 0) $head = $cid;
            }
            if ($head) $h->update(['head_citizen_id' => $head]);
        }
    }

    private function enrollPortal(array $citizens, int $n): void
    {
        foreach (collect($citizens)->shuffle()->take($n) as $cid) {
            Citizen::where('id', $cid)->update([
                'portal_email' => "resident{$cid}@example.com",
                'password' => Hash::make('password'),
                'portal_enrolled_at' => now()->subDays(random_int(1, 90)),
            ]);
        }
    }

    private function makeTriage(array $citizens, array $staff, int $n): array
    {
        $ids = [];
        for ($i = 0; $i < $n; $i++) {
            $t = TriageAssessment::create([
                'citizen_id' => Arr::random($citizens),
                'assessed_at' => $this->d(45),
                'chief_complaint' => Arr::random(['Fever','Cough and colds','Headache','Body pain','Hypertension check-up','Wound','Abdominal pain','Follow-up','Difficulty breathing']),
                'temperature' => random_int(360, 392) / 10,
                'systolic' => random_int(100, 160),
                'diastolic' => random_int(60, 100),
                'pulse' => random_int(60, 110),
                'respiratory_rate' => random_int(16, 26),
                'oxygen_saturation' => random_int(94, 100),
                'height_cm' => random_int(140, 180),
                'weight_kg' => random_int(40, 90),
                'pain_scale' => random_int(0, 8),
                'priority' => Arr::random(['non_urgent','non_urgent','non_urgent','urgent','emergent']),
                'status' => Arr::random(['done','done','seen','waiting']),
                'assessed_by' => Arr::random($staff['nurses'] ?: [null]),
            ]);
            $t->bmi = TriageAssessment::computeBmi($t->height_cm, $t->weight_kg);
            $t->save();
            $ids[] = $t->id;
        }
        return $ids;
    }

    private function makeConsultations(array $citizens, array $triages, array $staff, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $dx = Arr::random($this->diagnoses);
            $c = Consultation::create([
                'citizen_id' => Arr::random($citizens),
                'consulted_at' => $this->d(45),
                'physician_id' => Arr::random($staff['physicians']),
                'subjective' => 'Patient reports '.strtolower(Arr::random(['fever and cough','headache','body weakness','abdominal pain','follow-up concerns'])).' for '.random_int(1, 7).' days.',
                'objective' => 'Vital signs stable. '.Arr::random(['Clear breath sounds.','Mild pharyngeal congestion.','No acute distress.']),
                'assessment' => $dx,
                'plan' => Arr::random(['Home meds and rest','Continue maintenance','Advised follow-up','Refer for labs']),
                'diagnosis' => $dx,
                'disposition' => Arr::random(['home','home','follow_up','refer']),
                'follow_up_date' => random_int(0, 1) ? now()->addDays(random_int(7, 30))->toDateString() : null,
                'status' => 'completed',
            ]);
            foreach (range(0, random_int(0, 3)) as $r) {
                if ($r === 0) continue;
                $c->prescriptions()->create([
                    'drug_name' => Arr::random(['Paracetamol','Amoxicillin','Amlodipine','Metformin','Cetirizine','Mefenamic Acid','Salbutamol']),
                    'dosage' => Arr::random(['500 mg','250 mg','5 mg','10 mg']),
                    'frequency' => Arr::random(['OD','BID','TID','q6h']),
                    'duration' => random_int(3, 14).' days',
                    'quantity' => random_int(5, 30),
                ]);
            }
        }
    }

    private function makeAdmissions(array $citizens, array $staff, int $n): void
    {
        $beds = Bed::inRandomOrder()->get();
        if ($beds->isEmpty()) return;
        for ($i = 0; $i < $n; $i++) {
            $discharged = random_int(0, 1) === 1;
            $bed = $beds[$i % $beds->count()];
            $admittedAt = $this->d(30);
            $a = Admission::create([
                'citizen_id' => Arr::random($citizens),
                'ward_id' => $bed->ward_id,
                'bed_id' => $bed->id,
                'admitted_at' => $admittedAt,
                'admitting_diagnosis' => Arr::random(['Pneumonia','Dengue Fever','Normal Spontaneous Delivery','Gastroenteritis','Hypertensive Urgency']),
                'attending_physician' => 'Dr. '.Arr::random($this->surnames),
                'status' => $discharged ? 'discharged' : 'admitted',
                'discharged_at' => $discharged ? (clone $admittedAt)->addDays(random_int(1, 5)) : null,
                'outcome' => $discharged ? Arr::random(['improved','recovered','referred']) : null,
                'admitted_by' => Arr::random($staff['nurses'] ?: [null]),
            ]);
            if (! $discharged) {
                Bed::where('id', $bed->id)->update(['is_occupied' => true]);
            }
            foreach (range(1, random_int(1, 4)) as $r) {
                $a->nursingNotes()->create([
                    'noted_at' => (clone $admittedAt)->addHours($r * 6),
                    'note' => Arr::random(['Stable. Meds given.','Afebrile. Tolerating diet.','Ambulatory. No complaints.','IVF regulated.']),
                    'temperature' => random_int(360, 385) / 10,
                    'systolic' => random_int(100, 140), 'diastolic' => random_int(60, 90),
                    'pulse' => random_int(60, 100), 'respiratory_rate' => random_int(16, 22), 'oxygen_saturation' => random_int(95, 100),
                    'nurse_id' => Arr::random($staff['nurses'] ?: [null]),
                ]);
            }
        }
    }

    private function makeLabOrders(array $citizens, array $staff, int $n): void
    {
        $tests = LabTest::pluck('id')->all();
        for ($i = 0; $i < $n; $i++) {
            $status = Arr::random(['requested','in_process','completed','released','released']);
            $o = LabOrder::create([
                'citizen_id' => Arr::random($citizens),
                'ordered_by' => Arr::random($staff['physicians']),
                'ordered_at' => $this->d(40),
                'status' => $status,
                'released_at' => $status === 'released' ? $this->d(38) : null,
            ]);
            foreach ((array) Arr::random($tests, random_int(1, 4)) as $tid) {
                $done = in_array($status, ['completed','released']);
                $o->items()->create([
                    'lab_test_id' => $tid,
                    'result' => $done ? (string) random_int(1, 200) : null,
                    'flag' => $done ? Arr::random(['normal','normal','high','low','abnormal']) : null,
                ]);
            }
        }
    }

    private function makeRadiology(array $citizens, array $staff, int $n): void
    {
        $exams = ImagingExam::pluck('id')->all();
        for ($i = 0; $i < $n; $i++) {
            $status = Arr::random(['requested','performed','reported','released','released']);
            $reported = in_array($status, ['reported','released']);
            RadiologyOrder::create([
                'citizen_id' => Arr::random($citizens),
                'imaging_exam_id' => Arr::random($exams),
                'ordered_by' => Arr::random($staff['physicians']),
                'ordered_at' => $this->d(40),
                'clinical_indication' => Arr::random(['Rule out pneumonia','Cough x 2 weeks','Abdominal pain','Follow-up','Trauma']),
                'status' => $status,
                'performed_at' => $status !== 'requested' ? $this->d(38) : null,
                'findings' => $reported ? Arr::random(['No significant findings.','Hazy infiltrates noted.','Within normal limits.','Mild cardiomegaly.']) : null,
                'impression' => $reported ? Arr::random(['Essentially normal.','Consider pneumonia.','Unremarkable study.']) : null,
                'reported_by' => $reported ? $staff['radtech'] : null,
                'released_at' => $status === 'released' ? $this->d(36) : null,
            ]);
        }
    }

    private function stockAndDispense(array $citizens, array $staff, int $n): void
    {
        foreach (Medicine::all() as $m) {
            StockMovement::create([
                'medicine_id' => $m->id, 'type' => 'in', 'quantity' => random_int(300, 1500),
                'batch_no' => 'B'.random_int(1000, 9999), 'expiry_date' => now()->addMonths(random_int(6, 24))->toDateString(),
                'reference' => 'DOH Delivery', 'moved_at' => $this->d(60), 'user_id' => $staff['pharma'],
            ]);
        }
        $meds = Medicine::pluck('id')->all();
        for ($i = 0; $i < $n; $i++) {
            $d = Dispense::create([
                'citizen_id' => Arr::random($citizens),
                'dispensed_at' => $this->d(35),
                'dispensed_by' => $staff['pharma'],
            ]);
            foreach ((array) Arr::random($meds, random_int(1, 3)) as $mid) {
                $qty = random_int(1, 20);
                $d->items()->create(['medicine_id' => $mid, 'quantity' => $qty, 'instructions' => 'Take as directed']);
                StockMovement::create([
                    'medicine_id' => $mid, 'type' => 'out', 'quantity' => $qty,
                    'reference' => 'Dispense #'.$d->id, 'dispense_id' => $d->id, 'moved_at' => $d->dispensed_at, 'user_id' => $staff['pharma'],
                ]);
            }
        }
    }

    private function makeAssistance(array $citizens, array $staff, int $n): void
    {
        $types = AssistanceType::pluck('id')->all();
        for ($i = 0; $i < $n; $i++) {
            $status = Arr::random(['pending','approved','released','released','denied']);
            $req = random_int(1, 10) * 1000;
            AssistanceRequest::create([
                'citizen_id' => Arr::random($citizens),
                'assistance_type_id' => Arr::random($types),
                'request_date' => $this->d(60)->toDateString(),
                'purpose' => Arr::random(['Hospital bill assistance','Burial support','Medicines','School supplies','Food pack','Fare to referral hospital']),
                'amount_requested' => $req,
                'amount_granted' => in_array($status, ['released','approved']) ? min($req, random_int(1, 8) * 1000) : null,
                'status' => $status,
                'processed_by' => $staff['mswd'],
                'released_at' => $status === 'released' ? $this->d(50) : null,
            ]);
        }
    }

    private function makeAppointments(array $citizens, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            Appointment::create([
                'citizen_id' => Arr::random($citizens),
                'service' => Arr::random(['General Consultation','Prenatal Checkup','Immunization','Family Planning','Dental','Medical Certificate']),
                'preferred_date' => now()->addDays(random_int(-10, 20))->toDateString(),
                'preferred_time' => Arr::random(['8:00 AM','9:00 AM','10:00 AM','1:00 PM','2:00 PM']),
                'reason' => Arr::random(['Routine','Follow-up','New concern','Requirement']),
                'status' => Arr::random(['pending','pending','confirmed','completed','cancelled']),
                'source' => 'portal',
            ]);
        }
    }

    private function makeFieldVisits(array $citizens, array $staff, int $n): void
    {
        if (empty($staff['bhws'])) return;
        $byBarangay = Citizen::whereIn('id', $citizens)->get(['id', 'barangay_id', 'household_id'])->groupBy('barangay_id');
        for ($i = 0; $i < $n; $i++) {
            $bx = Arr::random(array_keys($staff['bhws']));
            $pool = $byBarangay->get($bx);
            $c = $pool && $pool->count() ? $pool->random() : null;
            FieldVisit::create([
                'bhw_id' => $staff['bhws'][$bx],
                'barangay_id' => $bx,
                'citizen_id' => $c?->id,
                'household_id' => $c?->household_id,
                'visit_date' => $this->d(40)->toDateString(),
                'visit_type' => Arr::random(['Prenatal','Postnatal','Newborn / Immunization','Family Planning','TB DOTS','Hypertension','Diabetes','Elderly / Senior','General']),
                'findings' => Arr::random(['No issues noted.','Advised follow-up at RHU.','BP elevated, referred.','Compliant with meds.','Newborn healthy.']),
                'action_taken' => Arr::random(['Health teaching given','Referred to RHU','Provided vitamins','Scheduled follow-up']),
                'referred' => random_int(1, 5) === 1,
            ]);
        }
    }

    private function makeSurveys(array $citizens, array $staff): void
    {
        $author = $staff['mswd'] ?? User::where('is_admin', true)->value('id');

        $s1 = Survey::create(['title' => 'Community Health Needs Assessment', 'description' => 'Annual barangay health profiling survey.', 'status' => 'published', 'is_public' => true, 'created_by' => $author]);
        $s1->questions()->createMany([
            ['sort_order' => 0, 'label' => 'Do you have access to clean drinking water?', 'type' => 'yesno', 'required' => true],
            ['sort_order' => 1, 'label' => 'What is your main source of income?', 'type' => 'radio', 'options' => ['Farming','Labor','Business','Employed','None'], 'required' => true],
            ['sort_order' => 2, 'label' => 'Which health services have you used this year?', 'type' => 'checkbox', 'options' => ['Consultation','Immunization','Family Planning','Dental','Laboratory']],
            ['sort_order' => 3, 'label' => 'How would you rate RHU services?', 'type' => 'rating'],
            ['sort_order' => 4, 'label' => 'Other comments', 'type' => 'paragraph'],
        ]);

        $s2 = Survey::create(['title' => 'Patient Satisfaction Survey', 'description' => 'Feedback after your RHU visit.', 'status' => 'published', 'is_public' => true, 'created_by' => $author]);
        $s2->questions()->createMany([
            ['sort_order' => 0, 'label' => 'Were you attended to promptly?', 'type' => 'yesno', 'required' => true],
            ['sort_order' => 1, 'label' => 'Overall satisfaction', 'type' => 'rating', 'required' => true],
            ['sort_order' => 2, 'label' => 'Suggestions', 'type' => 'paragraph'],
        ]);

        foreach ([$s1, $s2] as $survey) {
            $survey->load('questions');
            foreach (range(1, random_int(20, 35)) as $r) {
                $via = Arr::random(['public','public','bhw']);
                $cid = $via === 'bhw' ? Arr::random($citizens) : (random_int(0, 1) ? Arr::random($citizens) : null);
                $resp = SurveyResponse::create([
                    'survey_id' => $survey->id,
                    'citizen_id' => $cid,
                    'respondent_name' => $cid ? null : $this->pickName(Arr::random(['male','female'])),
                    'submitted_via' => $via,
                    'submitted_by' => $via === 'bhw' ? Arr::random($staff['bhws'] ?: [null]) : null,
                    'submitted_at' => $this->d(40),
                ]);
                foreach ($survey->questions as $q) {
                    $opts = $q->options ?? ['N/A'];
                    $ans = match ($q->type) {
                        'yesno' => Arr::random(['Yes','No']),
                        'radio' => Arr::random($opts),
                        'checkbox' => json_encode((array) Arr::random($opts, random_int(1, count($opts)))),
                        'rating' => (string) random_int(3, 5),
                        'paragraph' => Arr::random(['Good service.','Please add more staff.','Very satisfied.','Longer clinic hours please.']),
                        default => 'Sample',
                    };
                    $resp->answers()->create(['survey_question_id' => $q->id, 'answer' => $ans]);
                }
            }
        }
    }
}

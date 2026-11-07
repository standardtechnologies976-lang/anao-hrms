<?php

use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\PrescriptionItem;
use App\Models\TriageAssessment;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Consultation')]
class extends Component
{
    public ?int $editingId = null;
    public ?int $triage_assessment_id = null;
    public ?array $triageVitals = null;

    // Citizen picker
    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    // SOAP + diagnosis
    public string $subjective = '';
    public string $objective = '';
    public string $assessment = '';
    public string $plan = '';
    public string $diagnosis = '';
    public string $icd10_code = '';

    // Disposition
    public string $disposition = 'home';
    public string $follow_up_date = '';
    public string $referred_to = '';
    public string $referral_notes = '';

    // Prescriptions (repeater)
    public array $prescriptions = [];

    public function mount(?Consultation $consultation = null): void
    {
        if ($consultation && $consultation->exists) {
            $consultation->loadMissing(['citizen', 'prescriptions']);
            $this->editingId       = $consultation->id;
            $this->triage_assessment_id = $consultation->triage_assessment_id;
            $this->citizen_id      = $consultation->citizen_id;
            $this->citizenLabel    = $consultation->citizen->full_name.' ('.$consultation->citizen->citizen_no.')';
            $this->subjective      = $consultation->subjective ?? '';
            $this->objective       = $consultation->objective ?? '';
            $this->assessment      = $consultation->assessment ?? '';
            $this->plan            = $consultation->plan ?? '';
            $this->diagnosis       = $consultation->diagnosis ?? '';
            $this->icd10_code      = $consultation->icd10_code ?? '';
            $this->disposition     = $consultation->disposition;
            $this->follow_up_date  = $consultation->follow_up_date?->format('Y-m-d') ?? '';
            $this->referred_to     = $consultation->referred_to ?? '';
            $this->referral_notes  = $consultation->referral_notes ?? '';
            $this->prescriptions   = $consultation->prescriptions->map(fn ($p) => [
                'drug_name' => $p->drug_name, 'dosage' => $p->dosage, 'frequency' => $p->frequency,
                'duration' => $p->duration, 'quantity' => $p->quantity, 'instructions' => $p->instructions,
            ])->toArray();
            if ($this->triage_assessment_id) {
                $this->loadTriageVitals(TriageAssessment::find($this->triage_assessment_id));
            }
        } elseif ($tid = request()->integer('triage')) {
            $t = TriageAssessment::with('citizen')->find($tid);
            if ($t) {
                $this->triage_assessment_id = $t->id;
                $this->citizen_id = $t->citizen_id;
                $this->citizenLabel = $t->citizen->full_name.' ('.$t->citizen->citizen_no.')';
                $this->subjective = $t->chief_complaint ?? '';
                $this->loadTriageVitals($t);
            }
        }

        if (empty($this->prescriptions)) {
            $this->addPrescription();
        }
    }

    private function loadTriageVitals(?TriageAssessment $t): void
    {
        if (! $t) return;
        $this->triageVitals = array_filter([
            'BP' => $t->blood_pressure,
            'Temp' => $t->temperature ? $t->temperature.' C' : null,
            'Pulse' => $t->pulse,
            'RR' => $t->respiratory_rate,
            'O2' => $t->oxygen_saturation ? $t->oxygen_saturation.'%' : null,
            'BMI' => $t->bmi,
        ]);
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }
        return Citizen::query()->with('barangay')->search($this->citizenSearch)->orderBy('last_name')->limit(8)->get();
    }

    public function selectCitizen(int $id): void
    {
        $c = Citizen::findOrFail($id);
        $this->citizen_id = $c->id;
        $this->citizenLabel = $c->full_name.' ('.$c->citizen_no.')';
        $this->citizenSearch = '';
    }

    public function clearCitizen(): void
    {
        $this->citizen_id = '';
        $this->citizenLabel = '';
        $this->citizenSearch = '';
        $this->triage_assessment_id = null;
        $this->triageVitals = null;
    }

    public function addPrescription(): void
    {
        $this->prescriptions[] = ['drug_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'quantity' => '', 'instructions' => ''];
    }

    public function removePrescription(int $i): void
    {
        unset($this->prescriptions[$i]);
        $this->prescriptions = array_values($this->prescriptions);
    }

    public function save()
    {
        $data = $this->validate([
            'citizen_id'     => 'required|exists:citizens,id',
            'subjective'     => 'nullable|string',
            'objective'      => 'nullable|string',
            'assessment'     => 'nullable|string',
            'plan'           => 'nullable|string',
            'diagnosis'      => 'nullable|string|max:255',
            'icd10_code'     => 'nullable|string|max:20',
            'disposition'    => 'required|in:home,follow_up,refer,admit',
            'follow_up_date' => 'nullable|date',
            'referred_to'    => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
        ]);

        $data['triage_assessment_id'] = $this->triage_assessment_id;
        $data['follow_up_date'] = $data['follow_up_date'] ?: null;

        if ($this->editingId) {
            $consultation = Consultation::findOrFail($this->editingId);
            $consultation->update($data);
        } else {
            $data['consulted_at'] = now();
            $data['physician_id'] = auth()->id();
            $data['status'] = 'completed';
            $consultation = Consultation::create($data);
        }

        // Sync prescriptions (simple replace)
        $consultation->prescriptions()->delete();
        foreach ($this->prescriptions as $p) {
            if (trim($p['drug_name'] ?? '') === '') {
                continue;
            }
            $consultation->prescriptions()->create([
                'drug_name'    => $p['drug_name'],
                'dosage'       => $p['dosage'] ?: null,
                'frequency'    => $p['frequency'] ?: null,
                'duration'     => $p['duration'] ?: null,
                'quantity'     => $p['quantity'] !== '' ? (int) $p['quantity'] : null,
                'instructions' => $p['instructions'] ?: null,
            ]);
        }

        // Close out the triage entry
        if ($this->triage_assessment_id) {
            TriageAssessment::where('id', $this->triage_assessment_id)->update(['status' => 'done']);
        }

        Flux::toast(text: $this->editingId ? 'Consultation updated.' : 'Consultation saved.', variant: 'success');

        return $this->redirect(route('physician.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('physician.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Edit Consultation' : 'New Consultation' }}</flux:heading>
            <flux:text class="mt-1">SOAP notes, diagnosis, and medication orders</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Citizen --}}
        <flux:card class="space-y-3">
            <flux:heading size="lg">Patient</flux:heading>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    @unless ($editingId)
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    @endunless
                </div>
                @if ($triageVitals)
                    <div class="flex flex-wrap gap-2">
                        <flux:text size="sm" class="font-medium">Triage vitals:</flux:text>
                        @foreach ($triageVitals as $k => $v)
                            <flux:badge size="sm" color="zinc">{{ $k }} {{ $v }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            @else
                <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search patient by name or number..." icon="magnifying-glass" />
                @if ($this->citizenResults->isNotEmpty())
                    <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @foreach ($this->citizenResults as $c)
                            <button type="button" wire:click="selectCitizen({{ $c->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                {{ $c->full_name }} <span class="text-zinc-500">- {{ $c->citizen_no }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
            @error('citizen_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
        </flux:card>

        {{-- SOAP --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">SOAP Notes</flux:heading>
            <flux:textarea wire:model="subjective" label="Subjective" rows="3" placeholder="History, symptoms as reported" />
            <flux:textarea wire:model="objective" label="Objective" rows="3" placeholder="Exam findings, vitals" />
            <flux:textarea wire:model="assessment" label="Assessment" rows="2" placeholder="Clinical impression" />
            <flux:textarea wire:model="plan" label="Plan" rows="2" placeholder="Management plan" />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="diagnosis" label="Primary diagnosis" />
                <flux:input wire:model="icd10_code" label="ICD-10 code" placeholder="Optional" />
            </div>
        </flux:card>

        {{-- Prescriptions --}}
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Medication Orders</flux:heading>
                <flux:button size="sm" variant="filled" icon="plus" wire:click="addPrescription" type="button">Add drug</flux:button>
            </div>
            @forelse ($prescriptions as $i => $p)
                <div wire:key="rx-{{ $i }}" class="grid grid-cols-1 gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-12">
                    <flux:input wire:model="prescriptions.{{ $i }}.drug_name" placeholder="Drug name" class="sm:col-span-4" />
                    <flux:input wire:model="prescriptions.{{ $i }}.dosage" placeholder="Dosage" class="sm:col-span-2" />
                    <flux:input wire:model="prescriptions.{{ $i }}.frequency" placeholder="Freq (TID)" class="sm:col-span-2" />
                    <flux:input wire:model="prescriptions.{{ $i }}.duration" placeholder="Duration" class="sm:col-span-2" />
                    <flux:input wire:model="prescriptions.{{ $i }}.quantity" type="number" placeholder="Qty" class="sm:col-span-1" />
                    <div class="flex items-end sm:col-span-1">
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="removePrescription({{ $i }})" type="button" />
                    </div>
                    <flux:input wire:model="prescriptions.{{ $i }}.instructions" placeholder="Instructions (e.g., after meals)" class="sm:col-span-12" />
                </div>
            @empty
                <flux:text class="text-zinc-500">No medications ordered.</flux:text>
            @endforelse
        </flux:card>

        {{-- Disposition --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Disposition</flux:heading>
            <flux:radio.group wire:model.live="disposition" variant="segmented">
                <flux:radio value="home" label="Send home" />
                <flux:radio value="follow_up" label="Follow-up" />
                <flux:radio value="refer" label="Refer" />
                <flux:radio value="admit" label="Admit" />
            </flux:radio.group>
            @if ($disposition === 'follow_up')
                <flux:input wire:model="follow_up_date" label="Follow-up date" type="date" class="max-w-xs" />
            @endif
            @if ($disposition === 'refer')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="referred_to" label="Referred to" placeholder="Facility / specialist" />
                    <flux:input wire:model="referral_notes" label="Referral notes" />
                </div>
            @endif
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('physician.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Update Consultation' : 'Save Consultation' }}</flux:button>
        </div>
    </form>
</div>

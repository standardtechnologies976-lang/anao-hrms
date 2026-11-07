<?php

use App\Models\Citizen;
use App\Models\TriageAssessment;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Triage Assessment')]
class extends Component
{
    public ?int $editingId = null;

    // Citizen picker
    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    public string $chief_complaint = '';
    public $temperature = '';
    public $systolic = '';
    public $diastolic = '';
    public $pulse = '';
    public $respiratory_rate = '';
    public $oxygen_saturation = '';
    public $height_cm = '';
    public $weight_kg = '';
    public $pain_scale = '';
    public string $priority = 'non_urgent';
    public string $notes = '';

    public function mount(?TriageAssessment $triage = null): void
    {
        if ($triage && $triage->exists) {
            $triage->loadMissing('citizen');
            $this->editingId        = $triage->id;
            $this->citizen_id       = $triage->citizen_id;
            $this->citizenLabel     = $triage->citizen->full_name.' ('.$triage->citizen->citizen_no.')';
            $this->chief_complaint  = $triage->chief_complaint ?? '';
            $this->temperature      = $triage->temperature ?? '';
            $this->systolic         = $triage->systolic ?? '';
            $this->diastolic        = $triage->diastolic ?? '';
            $this->pulse            = $triage->pulse ?? '';
            $this->respiratory_rate = $triage->respiratory_rate ?? '';
            $this->oxygen_saturation = $triage->oxygen_saturation ?? '';
            $this->height_cm        = $triage->height_cm ?? '';
            $this->weight_kg        = $triage->weight_kg ?? '';
            $this->pain_scale       = $triage->pain_scale ?? '';
            $this->priority         = $triage->priority;
            $this->notes            = $triage->notes ?? '';
        }
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }

        return Citizen::query()
            ->with('barangay')
            ->search($this->citizenSearch)
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function bmiPreview(): ?float
    {
        return TriageAssessment::computeBmi(
            $this->height_cm !== '' ? (float) $this->height_cm : null,
            $this->weight_kg !== '' ? (float) $this->weight_kg : null,
        );
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
    }

    public function save()
    {
        $data = $this->validate([
            'citizen_id'        => 'required|exists:citizens,id',
            'chief_complaint'   => 'nullable|string',
            'temperature'       => 'nullable|numeric|between:25,45',
            'systolic'          => 'nullable|integer|between:40,300',
            'diastolic'         => 'nullable|integer|between:20,200',
            'pulse'             => 'nullable|integer|between:20,260',
            'respiratory_rate'  => 'nullable|integer|between:5,80',
            'oxygen_saturation' => 'nullable|integer|between:50,100',
            'height_cm'         => 'nullable|numeric|between:20,250',
            'weight_kg'         => 'nullable|numeric|between:1,400',
            'pain_scale'        => 'nullable|integer|between:0,10',
            'priority'          => 'required|in:emergent,urgent,non_urgent',
            'notes'             => 'nullable|string',
        ]);

        $data['bmi'] = TriageAssessment::computeBmi(
            $this->height_cm !== '' ? (float) $this->height_cm : null,
            $this->weight_kg !== '' ? (float) $this->weight_kg : null,
        );

        foreach (['temperature','systolic','diastolic','pulse','respiratory_rate','oxygen_saturation','height_cm','weight_kg','pain_scale'] as $f) {
            $data[$f] = $data[$f] === '' ? null : $data[$f];
        }

        if ($this->editingId) {
            TriageAssessment::findOrFail($this->editingId)->update($data);
        } else {
            $data['assessed_at'] = now();
            $data['assessed_by'] = auth()->id();
            $data['status'] = 'waiting';
            TriageAssessment::create($data);
        }

        Flux::toast(text: $this->editingId ? 'Triage updated.' : 'Triage recorded.', variant: 'success');

        return $this->redirect(route('triage.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('triage.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Edit Triage' : 'New Triage Assessment' }}</flux:heading>
            <flux:text class="mt-1">Record vitals and priority for a citizen visit</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Citizen --}}
        <flux:card class="space-y-3">
            <flux:heading size="lg">Citizen</flux:heading>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    @unless ($editingId)
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    @endunless
                </div>
            @else
                <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search citizen by name or number..." icon="magnifying-glass" />
                @if ($this->citizenResults->isNotEmpty())
                    <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @foreach ($this->citizenResults as $c)
                            <button type="button" wire:click="selectCitizen({{ $c->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                {{ $c->full_name }} <span class="text-zinc-500">- {{ $c->citizen_no }}, {{ $c->barangay?->name ?? 'no barangay' }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif (mb_strlen($citizenSearch) >= 2)
                    <flux:text class="text-zinc-500">No match. Register the citizen first in the Master Citizen Index.</flux:text>
                @endif
            @endif
            @error('citizen_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
        </flux:card>

        {{-- Complaint & vitals --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Assessment</flux:heading>
            <flux:textarea wire:model="chief_complaint" label="Chief complaint" rows="2" />

            <flux:separator text="Vital signs" />
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <flux:input wire:model="temperature" label="Temp (C)" type="number" step="0.1" />
                <flux:input wire:model="systolic" label="Systolic" type="number" />
                <flux:input wire:model="diastolic" label="Diastolic" type="number" />
                <flux:input wire:model="pulse" label="Pulse (bpm)" type="number" />
                <flux:input wire:model="respiratory_rate" label="Resp rate" type="number" />
                <flux:input wire:model="oxygen_saturation" label="O2 sat (%)" type="number" />
                <flux:input wire:model.live.debounce.500ms="height_cm" label="Height (cm)" type="number" step="0.1" />
                <flux:input wire:model.live.debounce.500ms="weight_kg" label="Weight (kg)" type="number" step="0.1" />
                <flux:input label="BMI" value="{{ $this->bmiPreview ?? '' }}" readonly disabled />
            </div>
            <flux:input wire:model="pain_scale" label="Pain scale (0-10)" type="number" class="max-w-xs" />
        </flux:card>

        {{-- Priority --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Priority</flux:heading>
            <flux:radio.group wire:model="priority" variant="segmented">
                <flux:radio value="emergent" label="Emergent" />
                <flux:radio value="urgent" label="Urgent" />
                <flux:radio value="non_urgent" label="Non-urgent" />
            </flux:radio.group>
            <flux:textarea wire:model="notes" label="Notes" rows="2" />
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('triage.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Update Triage' : 'Save Triage' }}</flux:button>
        </div>
    </form>
</div>

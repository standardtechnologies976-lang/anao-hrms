<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\Ward;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Admission')]
class extends Component
{
    public ?Admission $admission = null;
    public ?int $editingId = null;
    public string $status = 'admitted';
    private ?int $originalBedId = null;

    // Patient
    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    // Admission
    public $ward_id = '';
    public $bed_id = '';
    public string $admitting_diagnosis = '';
    public string $attending_physician = '';

    // Nursing note sub-form
    public string $nn_note = '';
    public $nn_temperature = '';
    public $nn_systolic = '';
    public $nn_diastolic = '';
    public $nn_pulse = '';
    public $nn_respiratory_rate = '';
    public $nn_oxygen_saturation = '';

    // Discharge
    public string $dc_diagnosis = '';
    public string $dc_outcome = '';
    public string $dc_notes = '';

    public function mount(?Admission $admission = null): void
    {
        if ($admission && $admission->exists) {
            $admission->loadMissing('citizen');
            $this->admission = $admission;
            $this->editingId = $admission->id;
            $this->status = $admission->status;
            $this->originalBedId = $admission->bed_id;
            $this->citizen_id = $admission->citizen_id;
            $this->citizenLabel = $admission->citizen->full_name.' ('.$admission->citizen->citizen_no.')';
            $this->ward_id = $admission->ward_id ?? '';
            $this->bed_id = $admission->bed_id ?? '';
            $this->admitting_diagnosis = $admission->admitting_diagnosis ?? '';
            $this->attending_physician = $admission->attending_physician ?? '';
            $this->dc_diagnosis = $admission->admitting_diagnosis ?? '';
        } elseif ($cid = request()->integer('consultation')) {
            $c = Consultation::with('citizen')->find($cid);
            if ($c) {
                $this->citizen_id = $c->citizen_id;
                $this->citizenLabel = $c->citizen->full_name.' ('.$c->citizen->citizen_no.')';
                $this->admitting_diagnosis = $c->diagnosis ?? '';
            }
        }
    }

    #[Computed]
    public function wards()
    {
        return Ward::where('active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function availableBeds()
    {
        if (! $this->ward_id) {
            return collect();
        }
        return Bed::where('ward_id', $this->ward_id)
            ->where(fn ($q) => $q->where('is_occupied', false)->orWhere('id', $this->originalBedId))
            ->orderBy('label')
            ->get();
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }
        return Citizen::query()->search($this->citizenSearch)->orderBy('last_name')->limit(8)->get();
    }

    public function updatedWardId(): void
    {
        $this->bed_id = '';
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
            'citizen_id'          => 'required|exists:citizens,id',
            'ward_id'             => 'required|exists:wards,id',
            'bed_id'              => 'nullable|exists:beds,id',
            'admitting_diagnosis' => 'nullable|string|max:255',
            'attending_physician' => 'nullable|string|max:255',
        ]);

        $data['bed_id'] = $data['bed_id'] ?: null;

        if ($this->editingId) {
            $admission = Admission::findOrFail($this->editingId);
            // Handle bed reassignment
            if ($this->originalBedId != $data['bed_id']) {
                if ($this->originalBedId) {
                    Bed::where('id', $this->originalBedId)->update(['is_occupied' => false]);
                }
                if ($data['bed_id']) {
                    Bed::where('id', $data['bed_id'])->update(['is_occupied' => true]);
                }
            }
            $admission->update($data);
            $this->originalBedId = $data['bed_id'];
            Flux::toast(text: 'Admission updated.', variant: 'success');
            return;
        }

        $data['admitted_at'] = now();
        $data['admitted_by'] = auth()->id();
        $data['status'] = 'admitted';
        $admission = Admission::create($data);
        if ($data['bed_id']) {
            Bed::where('id', $data['bed_id'])->update(['is_occupied' => true]);
        }

        Flux::toast(text: 'Patient admitted.', variant: 'success');
        return $this->redirect(route('nursing.edit', $admission), navigate: true);
    }

    public function addNote()
    {
        $data = $this->validate([
            'nn_note'              => 'nullable|string',
            'nn_temperature'       => 'nullable|numeric|between:25,45',
            'nn_systolic'          => 'nullable|integer|between:40,300',
            'nn_diastolic'         => 'nullable|integer|between:20,200',
            'nn_pulse'             => 'nullable|integer|between:20,260',
            'nn_respiratory_rate'  => 'nullable|integer|between:5,80',
            'nn_oxygen_saturation' => 'nullable|integer|between:50,100',
        ]);

        if (trim($this->nn_note) === '' && $this->nn_temperature === '' && $this->nn_systolic === '' && $this->nn_pulse === '') {
            $this->addError('nn_note', 'Enter a note or at least one vital sign.');
            return;
        }

        $this->admission->nursingNotes()->create([
            'noted_at'          => now(),
            'note'              => $this->nn_note ?: null,
            'temperature'       => $this->nn_temperature !== '' ? $this->nn_temperature : null,
            'systolic'          => $this->nn_systolic !== '' ? $this->nn_systolic : null,
            'diastolic'         => $this->nn_diastolic !== '' ? $this->nn_diastolic : null,
            'pulse'             => $this->nn_pulse !== '' ? $this->nn_pulse : null,
            'respiratory_rate'  => $this->nn_respiratory_rate !== '' ? $this->nn_respiratory_rate : null,
            'oxygen_saturation' => $this->nn_oxygen_saturation !== '' ? $this->nn_oxygen_saturation : null,
            'nurse_id'          => auth()->id(),
        ]);

        $this->reset(['nn_note', 'nn_temperature', 'nn_systolic', 'nn_diastolic', 'nn_pulse', 'nn_respiratory_rate', 'nn_oxygen_saturation']);
        Flux::toast(text: 'Nursing note added.', variant: 'success');
    }

    public function discharge()
    {
        $data = $this->validate([
            'dc_diagnosis' => 'nullable|string|max:255',
            'dc_outcome'   => 'required|in:improved,recovered,referred,transferred,expired,hama',
            'dc_notes'     => 'nullable|string',
        ]);

        $this->admission->update([
            'status'              => 'discharged',
            'discharged_at'       => now(),
            'discharge_diagnosis' => $this->dc_diagnosis ?: null,
            'outcome'             => $this->dc_outcome,
            'discharge_notes'     => $this->dc_notes ?: null,
        ]);

        if ($this->admission->bed_id) {
            Bed::where('id', $this->admission->bed_id)->update(['is_occupied' => false]);
        }

        Flux::toast(text: 'Patient discharged.', variant: 'success');
        return $this->redirect(route('nursing.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('nursing.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Admission Record' : 'Admit Patient' }}</flux:heading>
            <flux:text class="mt-1">
                {{ $editingId ? ($status === 'discharged' ? 'Discharged' : 'Currently admitted') : 'Assign a ward and bed' }}
            </flux:text>
        </div>
    </div>

    {{-- Admission details --}}
    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-3">
            <flux:heading size="lg">Patient</flux:heading>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    @unless ($editingId)
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    @endunless
                </div>
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

        <flux:card class="space-y-4">
            <flux:heading size="lg">Admission Details</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="ward_id" label="Ward" placeholder="Select ward...">
                    @foreach ($this->wards as $w)
                        <flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="bed_id" label="Bed" placeholder="{{ $ward_id ? 'Select bed...' : 'Choose a ward first' }}">
                    @foreach ($this->availableBeds as $b)
                        <flux:select.option :value="$b->id">{{ $b->label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="admitting_diagnosis" label="Admitting diagnosis" />
                <flux:input wire:model="attending_physician" label="Attending physician" />
            </div>
            @if ($status !== 'discharged')
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $editingId ? 'Save Changes' : 'Admit Patient' }}</flux:button>
                </div>
            @endif
        </flux:card>
    </form>

    {{-- Nursing notes + discharge (only for existing, admitted patients) --}}
    @if ($editingId)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Nursing Notes & Monitoring</flux:heading>

            @if ($status !== 'discharged')
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:textarea wire:model="nn_note" label="Note" rows="2" placeholder="Observation, medication given, etc." />
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
                        <flux:input wire:model="nn_temperature" label="Temp" type="number" step="0.1" />
                        <flux:input wire:model="nn_systolic" label="Sys" type="number" />
                        <flux:input wire:model="nn_diastolic" label="Dia" type="number" />
                        <flux:input wire:model="nn_pulse" label="Pulse" type="number" />
                        <flux:input wire:model="nn_respiratory_rate" label="RR" type="number" />
                        <flux:input wire:model="nn_oxygen_saturation" label="O2%" type="number" />
                    </div>
                    @error('nn_note') <flux:text color="red">{{ $message }}</flux:text> @enderror
                    <div class="flex justify-end">
                        <flux:button size="sm" variant="filled" icon="plus" wire:click="addNote" type="button">Add Note</flux:button>
                    </div>
                </div>
            @endif

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($admission->nursingNotes as $note)
                    <div class="py-2">
                        <div class="flex items-center justify-between">
                            <flux:text size="sm" class="font-medium">{{ $note->noted_at->format('M j, g:i A') }} · {{ $note->nurse?->name ?? 'Staff' }}</flux:text>
                            <div class="flex flex-wrap gap-1">
                                @if ($note->blood_pressure) <flux:badge size="sm" color="zinc">BP {{ $note->blood_pressure }}</flux:badge> @endif
                                @if ($note->temperature) <flux:badge size="sm" color="zinc">{{ $note->temperature }}C</flux:badge> @endif
                                @if ($note->pulse) <flux:badge size="sm" color="zinc">HR {{ $note->pulse }}</flux:badge> @endif
                                @if ($note->oxygen_saturation) <flux:badge size="sm" color="zinc">O2 {{ $note->oxygen_saturation }}%</flux:badge> @endif
                            </div>
                        </div>
                        @if ($note->note) <flux:text class="mt-1">{{ $note->note }}</flux:text> @endif
                    </div>
                @empty
                    <flux:text class="text-zinc-500">No nursing notes yet.</flux:text>
                @endforelse
            </div>
        </flux:card>

        @if ($status !== 'discharged')
            <flux:card class="space-y-4">
                <flux:heading size="lg">Discharge</flux:heading>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <flux:input wire:model="dc_diagnosis" label="Final diagnosis" />
                    <flux:select wire:model="dc_outcome" label="Outcome" placeholder="Select outcome...">
                        <flux:select.option value="improved">Improved</flux:select.option>
                        <flux:select.option value="recovered">Recovered</flux:select.option>
                        <flux:select.option value="referred">Referred</flux:select.option>
                        <flux:select.option value="transferred">Transferred</flux:select.option>
                        <flux:select.option value="hama">Home Against Medical Advice</flux:select.option>
                        <flux:select.option value="expired">Expired</flux:select.option>
                    </flux:select>
                </div>
                <flux:textarea wire:model="dc_notes" label="Discharge notes" rows="2" />
                <div class="flex justify-end">
                    <flux:button variant="danger" icon="arrow-right-start-on-rectangle" wire:click="discharge" wire:confirm="Discharge this patient and free the bed?">Discharge Patient</flux:button>
                </div>
            </flux:card>
        @else
            <flux:card class="space-y-2">
                <flux:heading size="lg">Discharge Summary</flux:heading>
                <flux:text>Discharged {{ $admission->discharged_at?->format('M j, Y g:i A') }} · Outcome: {{ str($admission->outcome)->replace('_',' ')->title() }}</flux:text>
                @if ($admission->discharge_diagnosis) <flux:text>Final diagnosis: {{ $admission->discharge_diagnosis }}</flux:text> @endif
                @if ($admission->discharge_notes) <flux:text>{{ $admission->discharge_notes }}</flux:text> @endif
            </flux:card>
        @endif
    @endif
</div>

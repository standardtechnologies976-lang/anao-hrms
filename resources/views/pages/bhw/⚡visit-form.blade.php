<?php

use App\Models\Citizen;
use App\Models\FieldVisit;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::bhw')]
#[Title('Log Visit')]
class extends Component
{
    public ?int $editingId = null;

    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    public string $visit_date = '';
    public string $visit_type = '';
    public string $findings = '';
    public string $action_taken = '';
    public bool $referred = false;
    public string $referral_notes = '';

    public function mount(?FieldVisit $visit = null): void
    {
        if ($visit && $visit->exists && $visit->bhw_id === auth()->id()) {
            $visit->loadMissing('citizen');
            $this->editingId = $visit->id;
            $this->citizen_id = $visit->citizen_id ?? '';
            $this->citizenLabel = $visit->citizen ? $visit->citizen->full_name.' ('.$visit->citizen->citizen_no.')' : '';
            $this->visit_date = $visit->visit_date->format('Y-m-d');
            $this->visit_type = $visit->visit_type;
            $this->findings = $visit->findings ?? '';
            $this->action_taken = $visit->action_taken ?? '';
            $this->referred = (bool) $visit->referred;
            $this->referral_notes = $visit->referral_notes ?? '';
            return;
        }

        $this->visit_date = now()->toDateString();
        if ($cid = request()->integer('citizen')) {
            $c = Citizen::find($cid);
            if ($c) {
                $this->citizen_id = $c->id;
                $this->citizenLabel = $c->full_name.' ('.$c->citizen_no.')';
            }
        }
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }
        $bx = auth()->user()->barangay_id;
        return Citizen::query()
            ->when($bx, fn ($q) => $q->where('barangay_id', $bx))
            ->search($this->citizenSearch)
            ->orderBy('last_name')
            ->limit(8)
            ->get();
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
        $this->reset(['citizen_id', 'citizenLabel', 'citizenSearch']);
    }

    public function save()
    {
        $data = $this->validate([
            'citizen_id'     => 'nullable|exists:citizens,id',
            'visit_date'     => 'required|date',
            'visit_type'     => 'required|string|max:100',
            'findings'       => 'nullable|string',
            'action_taken'   => 'nullable|string',
            'referred'       => 'boolean',
            'referral_notes' => 'nullable|string',
        ]);

        $citizen = $this->citizen_id ? Citizen::find($this->citizen_id) : null;
        $payload = $data + [
            'bhw_id'      => auth()->id(),
            'barangay_id' => $citizen?->barangay_id ?? auth()->user()->barangay_id,
            'citizen_id'  => $this->citizen_id ?: null,
            'household_id' => $citizen?->household_id,
        ];

        FieldVisit::updateOrCreate(['id' => $this->editingId], $payload);
        Flux::toast(text: 'Visit saved.', variant: 'success');
        return $this->redirect(route('bhw-portal.visits'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-4">
    <flux:heading size="lg">{{ $editingId ? 'Edit Visit' : 'Log a Field Visit' }}</flux:heading>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:card class="space-y-3">
            <flux:text class="font-medium">Resident (optional for household visits)</flux:text>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                </div>
            @else
                <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search resident..." icon="magnifying-glass" />
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
        </flux:card>

        <flux:card class="space-y-3">
            <flux:input wire:model="visit_date" label="Visit date" type="date" />
            <flux:select wire:model="visit_type" label="Visit type" placeholder="Select...">
                <flux:select.option value="Prenatal">Prenatal</flux:select.option>
                <flux:select.option value="Postnatal">Postnatal</flux:select.option>
                <flux:select.option value="Newborn / Immunization">Newborn / Immunization</flux:select.option>
                <flux:select.option value="Family Planning">Family Planning</flux:select.option>
                <flux:select.option value="TB DOTS">TB DOTS</flux:select.option>
                <flux:select.option value="Hypertension">Hypertension</flux:select.option>
                <flux:select.option value="Diabetes">Diabetes</flux:select.option>
                <flux:select.option value="Elderly / Senior">Elderly / Senior</flux:select.option>
                <flux:select.option value="General">General</flux:select.option>
            </flux:select>
            @error('visit_type') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:textarea wire:model="findings" label="Findings / observations" rows="3" />
            <flux:textarea wire:model="action_taken" label="Action taken / advice given" rows="2" />
            <flux:checkbox wire:model.live="referred" label="Referred to RHU" />
            @if ($referred)
                <flux:textarea wire:model="referral_notes" label="Referral notes" rows="2" />
            @endif
        </flux:card>

        <div class="flex gap-2">
            <flux:button variant="ghost" :href="route('bhw-portal.visits')" wire:navigate class="flex-1">Cancel</flux:button>
            <flux:button type="submit" variant="primary" class="flex-1">Save Visit</flux:button>
        </div>
    </form>
</div>

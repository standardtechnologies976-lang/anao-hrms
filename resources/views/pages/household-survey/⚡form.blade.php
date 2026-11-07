<?php

use App\Models\Barangay;
use App\Models\Citizen;
use App\Models\Household;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Household')]
class extends Component
{
    public ?Household $household = null;
    public ?int $editingId = null;
    public ?string $householdNo = null;

    public $barangay_id = '';
    public string $purok = '';
    public string $address = '';
    public string $house_ownership = '';
    public string $house_type = '';
    public string $water_source = '';
    public string $toilet_facility = '';
    public bool $has_electricity = false;
    public string $waste_disposal = '';
    public string $income_bracket = '';
    public string $surveyed_at = '';
    public string $remarks = '';

    // Member management
    public string $memberSearch = '';
    public string $newRelationship = '';

    public function mount(?Household $household = null): void
    {
        if ($household && $household->exists) {
            $this->household = $household;
            $this->editingId = $household->id;
            $this->householdNo = $household->household_no;
            $this->barangay_id = $household->barangay_id ?? '';
            $this->purok = $household->purok ?? '';
            $this->address = $household->address ?? '';
            $this->house_ownership = $household->house_ownership ?? '';
            $this->house_type = $household->house_type ?? '';
            $this->water_source = $household->water_source ?? '';
            $this->toilet_facility = $household->toilet_facility ?? '';
            $this->has_electricity = (bool) $household->has_electricity;
            $this->waste_disposal = $household->waste_disposal ?? '';
            $this->income_bracket = $household->income_bracket ?? '';
            $this->surveyed_at = $household->surveyed_at?->format('Y-m-d') ?? '';
            $this->remarks = $household->remarks ?? '';
        } else {
            $this->surveyed_at = now()->toDateString();
        }
    }

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    #[Computed]
    public function members()
    {
        return $this->household ? $this->household->members()->get() : collect();
    }

    #[Computed]
    public function memberResults()
    {
        if (mb_strlen($this->memberSearch) < 2) {
            return collect();
        }
        return Citizen::query()
            ->whereNull('household_id')
            ->search($this->memberSearch)
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function save()
    {
        $data = $this->validate([
            'barangay_id'     => 'nullable|exists:barangays,id',
            'purok'           => 'nullable|string|max:255',
            'address'         => 'nullable|string|max:255',
            'house_ownership' => 'nullable|string|max:100',
            'house_type'      => 'nullable|string|max:100',
            'water_source'    => 'nullable|string|max:255',
            'toilet_facility' => 'nullable|string|max:255',
            'has_electricity' => 'boolean',
            'waste_disposal'  => 'nullable|string|max:255',
            'income_bracket'  => 'nullable|string|max:100',
            'surveyed_at'     => 'nullable|date',
            'remarks'         => 'nullable|string',
        ]);

        $data['barangay_id'] = $data['barangay_id'] ?: null;
        $data['surveyed_at'] = $data['surveyed_at'] ?: null;

        if ($this->editingId) {
            $this->household->update($data);
            Flux::toast(text: 'Household updated.', variant: 'success');
            return;
        }

        $data['surveyed_by'] = auth()->id();
        $household = Household::create($data);
        Flux::toast(text: 'Household created. Add members below.', variant: 'success');
        return $this->redirect(route('household-survey.edit', $household), navigate: true);
    }

    public function addMember(int $citizenId): void
    {
        $citizen = Citizen::findOrFail($citizenId);
        $citizen->update([
            'household_id' => $this->household->id,
            'relationship_to_head' => $this->newRelationship ?: null,
            'barangay_id' => $citizen->barangay_id ?: $this->household->barangay_id,
        ]);
        $this->reset(['memberSearch', 'newRelationship']);
        Flux::toast(text: 'Member added.', variant: 'success');
    }

    public function removeMember(int $citizenId): void
    {
        $citizen = Citizen::findOrFail($citizenId);
        $citizen->update(['household_id' => null, 'relationship_to_head' => null]);
        if ($this->household->head_citizen_id === $citizenId) {
            $this->household->update(['head_citizen_id' => null]);
        }
        Flux::toast(text: 'Member removed.', variant: 'success');
    }

    public function setHead(int $citizenId): void
    {
        $this->household->update(['head_citizen_id' => $citizenId]);
        Citizen::where('id', $citizenId)->update(['relationship_to_head' => 'Head']);
        Flux::toast(text: 'Household head set.', variant: 'success');
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('household-survey.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Household Record' : 'New Household' }}</flux:heading>
            <flux:text class="mt-1">{{ $editingId ? $householdNo : 'Community profiling survey' }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">Location</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="barangay_id" label="Barangay" placeholder="Select barangay...">
                    @foreach ($this->barangayList as $b)
                        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="purok" label="Purok / Sitio" />
                <flux:input wire:model="address" label="House / street address" class="sm:col-span-2" />
                <flux:input wire:model="surveyed_at" label="Date surveyed" type="date" />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Housing & Utilities</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="house_ownership" label="House ownership" placeholder="Select...">
                    <flux:select.option value="Owned">Owned</flux:select.option>
                    <flux:select.option value="Rented">Rented</flux:select.option>
                    <flux:select.option value="Shared">Shared</flux:select.option>
                    <flux:select.option value="Informal Settler">Informal Settler</flux:select.option>
                </flux:select>
                <flux:select wire:model="house_type" label="House type" placeholder="Select...">
                    <flux:select.option value="Concrete">Concrete</flux:select.option>
                    <flux:select.option value="Semi-concrete">Semi-concrete</flux:select.option>
                    <flux:select.option value="Light materials">Light materials</flux:select.option>
                </flux:select>
                <flux:input wire:model="water_source" label="Water source" placeholder="Level II, deep well..." />
                <flux:input wire:model="toilet_facility" label="Toilet facility" placeholder="Water-sealed..." />
                <flux:input wire:model="waste_disposal" label="Waste disposal" placeholder="Collected, composting..." />
                <flux:input wire:model="income_bracket" label="Monthly income bracket" placeholder="Optional" />
            </div>
            <flux:checkbox wire:model="has_electricity" label="Has electricity" />
            <flux:textarea wire:model="remarks" label="Remarks" rows="2" />
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('household-survey.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Save Household' : 'Create Household' }}</flux:button>
        </div>
    </form>

    @if ($editingId)
        <flux:card class="space-y-4">
            <flux:heading size="lg">Household Members</flux:heading>

            <div class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <flux:text class="font-medium">Add a member</flux:text>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <flux:input wire:model.live.debounce.300ms="memberSearch" placeholder="Search citizen..." icon="magnifying-glass" class="sm:col-span-2" />
                    <flux:input wire:model="newRelationship" placeholder="Relationship (e.g. Spouse)" />
                </div>
                @if ($this->memberResults->isNotEmpty())
                    <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @foreach ($this->memberResults as $c)
                            <button type="button" wire:click="addMember({{ $c->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                {{ $c->full_name }} <span class="text-zinc-500">- {{ $c->citizen_no }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif (mb_strlen($memberSearch) >= 2)
                    <flux:text size="sm" class="text-zinc-500">No unassigned citizen found. Register them first in the Master Citizen Index.</flux:text>
                @endif
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->members as $m)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ $m->full_name }}</span>
                            <flux:text size="sm">
                                {{ $m->citizen_no }}
                                @if ($m->relationship_to_head) · {{ $m->relationship_to_head }} @endif
                                @if ($household->head_citizen_id === $m->id) <flux:badge size="sm" color="green">Head</flux:badge> @endif
                            </flux:text>
                        </div>
                        <div class="flex gap-1">
                            @if ($household->head_citizen_id !== $m->id)
                                <flux:button size="sm" variant="ghost" wire:click="setHead({{ $m->id }})">Set head</flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeMember({{ $m->id }})" wire:confirm="Remove from household?" />
                        </div>
                    </div>
                @empty
                    <flux:text class="text-zinc-500">No members yet. Add citizens above.</flux:text>
                @endforelse
            </div>
        </flux:card>
    @endif
</div>

<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Ward;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Nursing / Ward')]
class extends Component
{
    use WithPagination;

    public string $statusFilter = 'admitted';
    public string $wardFilter = '';

    #[Computed]
    public function admissions()
    {
        return Admission::query()
            ->with(['citizen', 'ward', 'bed'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->wardFilter, fn ($q) => $q->where('ward_id', $this->wardFilter))
            ->orderByDesc('admitted_at')
            ->paginate(15);
    }

    #[Computed]
    public function wards()
    {
        return Ward::orderBy('name')->get();
    }

    #[Computed]
    public function bedStats(): array
    {
        return [
            'total' => Bed::count(),
            'occupied' => Bed::where('is_occupied', true)->count(),
        ];
    }

    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedWardFilter(): void { $this->resetPage(); }
};
?>

@php $statusColor = ['admitted' => 'green', 'discharged' => 'zinc']; @endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Nursing / Ward Management</flux:heading>
            <flux:text class="mt-1">
                Admissions and bed occupancy · {{ $this->bedStats['occupied'] }}/{{ $this->bedStats['total'] }} beds in use
            </flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('nursing.create')" wire:navigate>Admit Patient</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:select wire:model.live="statusFilter" class="max-w-xs">
            <flux:select.option value="admitted">Currently admitted</flux:select.option>
            <flux:select.option value="discharged">Discharged</flux:select.option>
            <flux:select.option value="">All</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="wardFilter" placeholder="All wards" class="max-w-xs">
            <flux:select.option value="">All wards</flux:select.option>
            @foreach ($this->wards as $w)
                <flux:select.option :value="$w->id">{{ $w->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->admissions">
        <flux:table.columns>
            <flux:table.column>Admitted</flux:table.column>
            <flux:table.column>Patient</flux:table.column>
            <flux:table.column>Ward / Bed</flux:table.column>
            <flux:table.column>Admitting Dx</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->admissions as $a)
                <flux:table.row :key="$a->id">
                    <flux:table.cell>{{ $a->admitted_at->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $a->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $a->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $a->ward?->name ?? '-' }} {{ $a->bed ? '· '.$a->bed->label : '' }}</flux:table.cell>
                    <flux:table.cell>{{ $a->admitting_diagnosis ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$a->status]" size="sm">{{ ucfirst($a->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('nursing.edit', $a)" wire:navigate>Open</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No admissions found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

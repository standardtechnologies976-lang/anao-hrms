<?php

use App\Models\Appointment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Appointments')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending';

    #[Computed]
    public function appointments()
    {
        return Appointment::query()
            ->with('citizen')
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->whereHas('citizen', fn ($c) => $c->search($this->search))->orWhere('reference_no', 'like', "%{$this->search}%")))
            ->orderBy('preferred_date')
            ->paginate(15);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
};
?>

@php $statusColor = ['pending' => 'amber', 'confirmed' => 'blue', 'completed' => 'green', 'cancelled' => 'red']; @endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Appointments</flux:heading>
            <flux:text class="mt-1">Requests booked through the Citizen Portal</flux:text>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search patient or reference no..." icon="magnifying-glass" class="max-w-sm" />
        <flux:select wire:model.live="statusFilter" class="max-w-xs">
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="confirmed">Confirmed</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
            <flux:select.option value="cancelled">Cancelled</flux:select.option>
            <flux:select.option value="">All</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->appointments">
        <flux:table.columns>
            <flux:table.column>Ref No</flux:table.column>
            <flux:table.column>Patient</flux:table.column>
            <flux:table.column>Service</flux:table.column>
            <flux:table.column>Preferred</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->appointments as $a)
                <flux:table.row :key="$a->id">
                    <flux:table.cell variant="strong">{{ $a->reference_no }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $a->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $a->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $a->service }}</flux:table.cell>
                    <flux:table.cell>{{ $a->preferred_date->format('M j, Y') }} {{ $a->preferred_time }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$a->status]" size="sm">{{ ucfirst($a->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('appointments.edit', $a)" wire:navigate>Manage</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No appointments.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

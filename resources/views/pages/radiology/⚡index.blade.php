<?php

use App\Models\RadiologyOrder;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Radiology')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    #[Computed]
    public function orders()
    {
        return RadiologyOrder::query()
            ->with(['citizen', 'exam'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) => $q->whereHas('citizen', fn ($c) => $c->search($this->search)))
            ->orderByDesc('ordered_at')
            ->paginate(15);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
};
?>

@php $statusColor = ['requested' => 'blue', 'performed' => 'amber', 'reported' => 'green', 'released' => 'zinc']; @endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Radiology</flux:heading>
            <flux:text class="mt-1">Imaging requests, findings, and reports</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('radiology.create')" wire:navigate>New Imaging Request</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by patient..." icon="magnifying-glass" class="max-w-md" />
        <flux:select wire:model.live="statusFilter" placeholder="All statuses" class="max-w-xs">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="requested">Requested</flux:select.option>
            <flux:select.option value="performed">Performed</flux:select.option>
            <flux:select.option value="reported">Reported</flux:select.option>
            <flux:select.option value="released">Released</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Ordered</flux:table.column>
            <flux:table.column>Patient</flux:table.column>
            <flux:table.column>Exam</flux:table.column>
            <flux:table.column>Modality</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->orders as $o)
                <flux:table.row :key="$o->id">
                    <flux:table.cell>{{ $o->ordered_at->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $o->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $o->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $o->exam->name }}</flux:table.cell>
                    <flux:table.cell>{{ $o->exam->modality }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$o->status]" size="sm">{{ ucfirst($o->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('radiology.edit', $o)" wire:navigate>Open</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No imaging requests yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

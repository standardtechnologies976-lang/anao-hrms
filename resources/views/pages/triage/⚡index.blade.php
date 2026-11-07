<?php

use App\Models\TriageAssessment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Triage Assessment')]
class extends Component
{
    use WithPagination;

    public string $dateFilter = '';
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->dateFilter = now()->toDateString();
    }

    #[Computed]
    public function assessments()
    {
        return TriageAssessment::query()
            ->with('citizen.barangay')
            ->when($this->dateFilter, fn ($q) => $q->whereDate('assessed_at', $this->dateFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("FIELD(status, 'waiting', 'seen', 'done')")
            ->orderByRaw("FIELD(priority, 'emergent', 'urgent', 'non_urgent')")
            ->orderByDesc('assessed_at')
            ->paginate(15);
    }

    public function updatedDateFilter(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function setStatus(int $id, string $status): void
    {
        if (! in_array($status, ['waiting', 'seen', 'done'], true)) {
            return;
        }
        TriageAssessment::findOrFail($id)->update(['status' => $status]);
    }

    public function delete(int $id): void
    {
        TriageAssessment::findOrFail($id)->delete();
        Flux::toast(text: 'Triage record removed.', variant: 'success');
    }
};
?>

@php
    $priorityColor = ['emergent' => 'red', 'urgent' => 'amber', 'non_urgent' => 'green'];
    $statusColor = ['waiting' => 'blue', 'seen' => 'amber', 'done' => 'zinc'];
@endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Triage Assessment</flux:heading>
            <flux:text class="mt-1">Vitals, chief complaint, and priority for the day's queue</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('triage.create')" wire:navigate>New Triage</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input type="date" wire:model.live="dateFilter" class="max-w-xs" />
        <flux:select wire:model.live="statusFilter" placeholder="All statuses" class="max-w-xs">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="waiting">Waiting</flux:select.option>
            <flux:select.option value="seen">Seen</flux:select.option>
            <flux:select.option value="done">Done</flux:select.option>
        </flux:select>
    </div>

    <flux:table :paginate="$this->assessments">
        <flux:table.columns>
            <flux:table.column>Time</flux:table.column>
            <flux:table.column>Citizen</flux:table.column>
            <flux:table.column>Complaint</flux:table.column>
            <flux:table.column>BP</flux:table.column>
            <flux:table.column>Temp</flux:table.column>
            <flux:table.column>Pulse</flux:table.column>
            <flux:table.column>O2</flux:table.column>
            <flux:table.column>Priority</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->assessments as $t)
                <flux:table.row :key="$t->id">
                    <flux:table.cell>{{ $t->assessed_at->format('g:i A') }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $t->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $t->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell class="max-w-xs truncate">{{ $t->chief_complaint ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $t->blood_pressure ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $t->temperature ? $t->temperature.'C' : '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $t->pulse ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $t->oxygen_saturation ? $t->oxygen_saturation.'%' : '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$priorityColor[$t->priority]" size="sm">
                            {{ str($t->priority)->replace('_', ' ')->title() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$t->status]" size="sm">{{ ucfirst($t->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" />
                            <flux:menu>
                                <flux:menu.item icon="pencil" :href="route('triage.edit', $t)" wire:navigate>Edit</flux:menu.item>
                                <flux:menu.item icon="check" wire:click="setStatus({{ $t->id }}, 'seen')">Mark Seen</flux:menu.item>
                                <flux:menu.item icon="check-circle" wire:click="setStatus({{ $t->id }}, 'done')">Mark Done</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $t->id }})" wire:confirm="Remove this triage record?">Delete</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="10" class="text-center">No triage records for this day.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

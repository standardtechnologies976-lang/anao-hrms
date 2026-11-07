<?php

use App\Models\Consultation;
use App\Models\TriageAssessment;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Physician - CPOE')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function queue()
    {
        return TriageAssessment::query()
            ->with('citizen')
            ->whereDate('assessed_at', now()->toDateString())
            ->whereIn('status', ['waiting', 'seen'])
            ->whereDoesntHave('consultation')
            ->orderByRaw("FIELD(priority, 'emergent', 'urgent', 'non_urgent')")
            ->orderBy('assessed_at')
            ->get();
    }

    #[Computed]
    public function consultations()
    {
        return Consultation::query()
            ->with(['citizen', 'physician'])
            ->when($this->search, fn ($q) => $q->whereHas('citizen', fn ($c) => $c->search($this->search)))
            ->orderByDesc('consulted_at')
            ->paginate(12);
    }

    public function updatedSearch(): void { $this->resetPage(); }
};
?>

@php $priorityColor = ['emergent' => 'red', 'urgent' => 'amber', 'non_urgent' => 'green']; @endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Physician - CPOE</flux:heading>
            <flux:text class="mt-1">Consultations, diagnosis, and orders</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('physician.create')" wire:navigate>New Consultation</flux:button>
    </div>

    {{-- Triage queue --}}
    <flux:card class="space-y-3">
        <div class="flex items-center gap-2">
            <flux:heading size="lg">Triage Queue Today</flux:heading>
            <flux:badge size="sm">{{ $this->queue->count() }} waiting</flux:badge>
        </div>

        @if ($this->queue->isEmpty())
            <flux:text class="text-zinc-500">No patients waiting. New arrivals show here after triage.</flux:text>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->queue as $t)
                    <div class="flex items-center justify-between gap-3 py-2">
                        <div class="flex items-center gap-3">
                            <flux:badge :color="$priorityColor[$t->priority]" size="sm">{{ str($t->priority)->replace('_',' ')->title() }}</flux:badge>
                            <div>
                                <div class="font-medium">{{ $t->citizen->full_name }}</div>
                                <flux:text size="sm">{{ $t->citizen->citizen_no }} · {{ $t->chief_complaint ?: 'No complaint noted' }}</flux:text>
                            </div>
                        </div>
                        <flux:button size="sm" variant="primary" icon="play" :href="route('physician.create', ['triage' => $t->id])" wire:navigate>Start Consult</flux:button>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>

    {{-- Recent consultations --}}
    <div class="flex flex-col gap-3">
        <flux:heading size="lg">Recent Consultations</flux:heading>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by citizen..." icon="magnifying-glass" class="max-w-md" />

        <flux:table :paginate="$this->consultations">
            <flux:table.columns>
                <flux:table.column>Date</flux:table.column>
                <flux:table.column>Citizen</flux:table.column>
                <flux:table.column>Diagnosis</flux:table.column>
                <flux:table.column>Disposition</flux:table.column>
                <flux:table.column>Physician</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->consultations as $c)
                    <flux:table.row :key="$c->id">
                        <flux:table.cell>{{ $c->consulted_at->format('M j, Y g:i A') }}</flux:table.cell>
                        <flux:table.cell variant="strong">
                            {{ $c->citizen->full_name }}
                            <flux:text size="sm" class="block">{{ $c->citizen->citizen_no }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>{{ $c->diagnosis ?: '-' }}</flux:table.cell>
                        <flux:table.cell>{{ str($c->disposition)->replace('_',' ')->title() }}</flux:table.cell>
                        <flux:table.cell>{{ $c->physician?->name ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('physician.edit', $c)" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center">No consultations yet.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>

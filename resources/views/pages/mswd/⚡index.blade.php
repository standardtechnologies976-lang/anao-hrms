<?php

use App\Models\AssistanceRequest;
use App\Models\AssistanceType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('MSWD / Social Services')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $typeFilter = '';

    #[Computed]
    public function requests()
    {
        return AssistanceRequest::query()
            ->with(['citizen', 'type'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn ($q) => $q->where('assistance_type_id', $this->typeFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->whereHas('citizen', fn ($c) => $c->search($this->search))->orWhere('reference_no', 'like', "%{$this->search}%")))
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function types()
    {
        return AssistanceType::orderBy('name')->get();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedTypeFilter(): void { $this->resetPage(); }
};
?>

@php $statusColor = ['pending' => 'amber', 'approved' => 'blue', 'released' => 'green', 'denied' => 'red']; @endphp

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">MSWD / Social Services</flux:heading>
            <flux:text class="mt-1">Assistance requests and case processing</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('mswd.create')" wire:navigate>New Request</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search patient or reference no..." icon="magnifying-glass" class="max-w-sm" />
        <flux:select wire:model.live="statusFilter" placeholder="All statuses" class="max-w-xs">
            <flux:select.option value="">All statuses</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="approved">Approved</flux:select.option>
            <flux:select.option value="released">Released</flux:select.option>
            <flux:select.option value="denied">Denied</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="typeFilter" placeholder="All types" class="max-w-xs">
            <flux:select.option value="">All types</flux:select.option>
            @foreach ($this->types as $t)
                <flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->requests">
        <flux:table.columns>
            <flux:table.column>Ref No</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Citizen</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Granted</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->requests as $r)
                <flux:table.row :key="$r->id">
                    <flux:table.cell variant="strong">{{ $r->reference_no }}</flux:table.cell>
                    <flux:table.cell>{{ $r->request_date->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $r->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $r->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $r->type->name }}</flux:table.cell>
                    <flux:table.cell>{{ $r->amount_granted ? 'PHP '.number_format($r->amount_granted, 2) : '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$r->status]" size="sm">{{ ucfirst($r->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('mswd.edit', $r)" wire:navigate>Open</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center">No assistance requests yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

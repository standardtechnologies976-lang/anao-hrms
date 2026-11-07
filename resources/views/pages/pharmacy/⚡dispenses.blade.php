<?php

use App\Models\Dispense;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Dispensing')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function dispenses()
    {
        return Dispense::query()
            ->with(['citizen', 'items'])
            ->withCount('items')
            ->when($this->search, fn ($q) => $q->whereHas('citizen', fn ($c) => $c->search($this->search)))
            ->orderByDesc('dispensed_at')
            ->paginate(15);
    }

    public function updatedSearch(): void { $this->resetPage(); }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Dispensing</flux:heading>
            <flux:text class="mt-1">Medicines issued to patients</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button icon="arrow-left" :href="route('pharmacy.index')" wire:navigate>Inventory</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('pharmacy.dispense.create')" wire:navigate>New Dispense</flux:button>
        </div>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by patient..." icon="magnifying-glass" class="max-w-md" />

    <flux:table :paginate="$this->dispenses">
        <flux:table.columns>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Patient</flux:table.column>
            <flux:table.column>Items</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->dispenses as $d)
                <flux:table.row :key="$d->id">
                    <flux:table.cell>{{ $d->dispensed_at->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell variant="strong">
                        {{ $d->citizen->full_name }}
                        <flux:text size="sm" class="block">{{ $d->citizen->citizen_no }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $d->items_count }} item{{ $d->items_count === 1 ? '' : 's' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="eye" :href="route('pharmacy.dispense.edit', $d)" wire:navigate>View</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" class="text-center">No dispensing records yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

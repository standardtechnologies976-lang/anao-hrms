<?php

use App\Models\Medicine;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Medicine Inventory')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $lowOnly = false;

    #[Computed]
    public function medicines()
    {
        return Medicine::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$this->search}%")->orWhere('generic_name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(20);
    }

    public function updatedSearch(): void { $this->resetPage(); }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Medicine Inventory & Dispensing</flux:heading>
            <flux:text class="mt-1">Drug stock levels and pharmacy issuance</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button icon="clipboard-document-list" :href="route('pharmacy.dispenses')" wire:navigate>Dispensing</flux:button>
            <flux:button icon="truck" :href="route('pharmacy.receive')" wire:navigate>Receive Stock</flux:button>
            <flux:button variant="primary" icon="plus" :href="route('pharmacy.medicine.create')" wire:navigate>Add Medicine</flux:button>
        </div>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search medicine or generic..." icon="magnifying-glass" class="max-w-md" />

    <flux:table :paginate="$this->medicines">
        <flux:table.columns>
            <flux:table.column>Medicine</flux:table.column>
            <flux:table.column>Generic</flux:table.column>
            <flux:table.column>Form</flux:table.column>
            <flux:table.column>On Hand</flux:table.column>
            <flux:table.column>Reorder At</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->medicines as $m)
                @php $stock = $m->currentStock(); @endphp
                <flux:table.row :key="$m->id">
                    <flux:table.cell variant="strong">{{ $m->name }} {{ $m->strength }}</flux:table.cell>
                    <flux:table.cell>{{ $m->generic_name ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $m->form ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($m->needsReorder())
                            <flux:badge color="red" size="sm">{{ $stock }} {{ $m->unit }} low</flux:badge>
                        @else
                            {{ $stock }} {{ $m->unit }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $m->reorder_level }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="plus" :href="route('pharmacy.receive', ['medicine' => $m->id])" wire:navigate>Receive</flux:button>
                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('pharmacy.medicine.edit', $m)" wire:navigate />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No medicines. Add one to start.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

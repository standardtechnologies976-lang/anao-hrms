<?php

use App\Models\Barangay;
use App\Models\Citizen;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Master Citizen Index')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $barangayFilter = '';

    #[Computed]
    public function citizens()
    {
        return Citizen::query()
            ->with('barangay')
            ->search($this->search)
            ->when($this->barangayFilter, fn ($q) => $q->where('barangay_id', $this->barangayFilter))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedBarangayFilter(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        Citizen::findOrFail($id)->delete();
        Flux::toast(text: 'Citizen record archived.', variant: 'success');
    }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Master Citizen Index</flux:heading>
            <flux:text class="mt-1">Municipality of Anao, Tarlac</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('citizens.create')" wire:navigate>Register Citizen</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name, citizen no, PhilHealth, contact..." icon="magnifying-glass" class="max-w-md" />
        <flux:select wire:model.live="barangayFilter" placeholder="All barangays" class="max-w-xs">
            <flux:select.option value="">All barangays</flux:select.option>
            @foreach ($this->barangayList as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->citizens">
        <flux:table.columns>
            <flux:table.column>Citizen No</flux:table.column>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Sex</flux:table.column>
            <flux:table.column>Age</flux:table.column>
            <flux:table.column>Barangay</flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column>Flags</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->citizens as $citizen)
                <flux:table.row :key="$citizen->id">
                    <flux:table.cell variant="strong">{{ $citizen->citizen_no }}</flux:table.cell>
                    <flux:table.cell>
                        {{ $citizen->full_name }}
                        @if ($citizen->deceased)
                            <flux:badge color="zinc" size="sm">Deceased</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $citizen->sex ? ucfirst($citizen->sex) : '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $citizen->age ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $citizen->barangay?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $citizen->contact_no ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @if ($citizen->is_senior) <flux:badge color="amber" size="sm">Senior</flux:badge> @endif
                            @if ($citizen->is_pwd) <flux:badge color="blue" size="sm">PWD</flux:badge> @endif
                            @if ($citizen->is_4ps) <flux:badge color="green" size="sm">4Ps</flux:badge> @endif
                            @if ($citizen->is_solo_parent) <flux:badge color="purple" size="sm">Solo</flux:badge> @endif
                            @if ($citizen->is_pregnant) <flux:badge color="pink" size="sm">Pregnant</flux:badge> @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('citizens.edit', $citizen)" wire:navigate />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $citizen->id }})" wire:confirm="Archive this citizen record?" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center">No citizen records found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

<?php

use App\Models\Barangay;
use App\Models\Household;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Household Survey')]
class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $barangayFilter = '';

    #[Computed]
    public function households()
    {
        return Household::query()
            ->with(['barangay', 'head'])
            ->withCount('members')
            ->when($this->barangayFilter, fn ($q) => $q->where('barangay_id', $this->barangayFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('household_no', 'like', "%{$this->search}%")
                  ->orWhereHas('head', fn ($h) => $h->search($this->search))))
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedBarangayFilter(): void { $this->resetPage(); }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Household Survey</flux:heading>
            <flux:text class="mt-1">Community profiling and household registry</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('household-survey.create')" wire:navigate>New Household</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search household no or head..." icon="magnifying-glass" class="max-w-sm" />
        <flux:select wire:model.live="barangayFilter" placeholder="All barangays" class="max-w-xs">
            <flux:select.option value="">All barangays</flux:select.option>
            @foreach ($this->barangayList as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table :paginate="$this->households">
        <flux:table.columns>
            <flux:table.column>Household No</flux:table.column>
            <flux:table.column>Barangay</flux:table.column>
            <flux:table.column>Head</flux:table.column>
            <flux:table.column>Members</flux:table.column>
            <flux:table.column>Surveyed</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->households as $h)
                <flux:table.row :key="$h->id">
                    <flux:table.cell variant="strong">{{ $h->household_no }}</flux:table.cell>
                    <flux:table.cell>{{ $h->barangay?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $h->head?->full_name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $h->members_count }}</flux:table.cell>
                    <flux:table.cell>{{ $h->surveyed_at?->format('M j, Y') ?? '-' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="arrow-right" :href="route('household-survey.edit', $h)" wire:navigate>Open</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No households surveyed yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

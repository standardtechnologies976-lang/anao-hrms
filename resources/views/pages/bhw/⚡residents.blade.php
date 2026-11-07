<?php

use App\Models\Citizen;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::bhw')]
#[Title('Residents')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function residents()
    {
        $bx = auth()->user()->barangay_id;
        return Citizen::query()
            ->when($bx, fn ($q) => $q->where('barangay_id', $bx))
            ->search($this->search)
            ->orderBy('last_name')
            ->paginate(15);
    }

    public function updatedSearch(): void { $this->resetPage(); }
};
?>

<div class="flex flex-col gap-3">
    <flux:heading size="lg">Residents</flux:heading>
    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search resident..." icon="magnifying-glass" />

    <div class="flex flex-col gap-2">
        @forelse ($this->residents as $c)
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $c->full_name }}</div>
                    <flux:text size="sm">{{ $c->citizen_no }} @if ($c->age) · {{ $c->age }} yrs @endif</flux:text>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @if ($c->is_pregnant) <flux:badge color="pink" size="sm">Pregnant</flux:badge> @endif
                        @if ($c->is_senior) <flux:badge color="amber" size="sm">Senior</flux:badge> @endif
                        @if ($c->is_pwd) <flux:badge color="blue" size="sm">PWD</flux:badge> @endif
                        @if ($c->is_4ps) <flux:badge color="green" size="sm">4Ps</flux:badge> @endif
                    </div>
                </div>
                <flux:button size="sm" variant="primary" icon="plus" :href="route('bhw-portal.visit.create', ['citizen' => $c->id])" wire:navigate>Visit</flux:button>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No residents found.</flux:text>
        @endforelse
    </div>
    <div>{{ $this->residents->links() }}</div>
</div>

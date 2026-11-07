<?php

use App\Models\FieldVisit;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::bhw')]
#[Title('My Visits')]
class extends Component
{
    use WithPagination;

    #[Computed]
    public function visits()
    {
        return FieldVisit::where('bhw_id', auth()->id())
            ->with('citizen')
            ->orderByDesc('visit_date')
            ->paginate(15);
    }
};
?>

<div class="flex flex-col gap-3">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">My Visits</flux:heading>
        <flux:button size="sm" variant="primary" icon="plus" :href="route('bhw-portal.visit.create')" wire:navigate>New</flux:button>
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($this->visits as $v)
            <flux:card>
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ $v->citizen?->full_name ?? 'Household visit' }}</span>
                    <flux:text size="sm">{{ $v->visit_date->format('M j, Y') }}</flux:text>
                </div>
                <flux:text size="sm">{{ $v->visit_type }}</flux:text>
                @if ($v->findings) <flux:text size="sm" class="mt-1 block">{{ Str::limit($v->findings, 100) }}</flux:text> @endif
                @if ($v->referred) <flux:badge color="red" size="sm" class="mt-1">Referred to RHU</flux:badge> @endif
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No visits yet.</flux:text>
        @endforelse
    </div>
    <div>{{ $this->visits->links() }}</div>
</div>

<?php

use App\Models\Survey;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::bhw')]
#[Title('Surveys')]
class extends Component
{
    #[Computed]
    public function surveys()
    {
        return Survey::where('status', 'published')->withCount('questions')->orderBy('title')->get();
    }
};
?>

<div class="flex flex-col gap-3">
    <flux:heading size="lg">Field Surveys</flux:heading>
    <flux:text>Pick a survey to fill out during a house visit.</flux:text>

    <div class="flex flex-col gap-2">
        @forelse ($this->surveys as $s)
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $s->title }}</div>
                    <flux:text size="sm">{{ $s->questions_count }} questions</flux:text>
                </div>
                <flux:button size="sm" variant="primary" icon="pencil-square" :href="route('bhw-portal.survey.fill', $s)" wire:navigate>Fill</flux:button>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No published surveys available.</flux:text>
        @endforelse
    </div>
</div>

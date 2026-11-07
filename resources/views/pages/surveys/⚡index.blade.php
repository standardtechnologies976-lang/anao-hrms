<?php

use App\Models\Survey;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Surveys')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function surveys()
    {
        return Survey::query()
            ->withCount(['questions', 'responses'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->orderByDesc('id')
            ->paginate(12);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        Survey::findOrFail($id)->delete();
        Flux::toast(text: 'Survey deleted.', variant: 'success');
    }
};
?>

@php $statusColor = ['draft' => 'zinc', 'published' => 'green', 'closed' => 'red']; @endphp

<div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Surveys</flux:heading>
            <flux:text class="mt-1">Build surveys, share by link or QR, and collect responses</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('surveys.create')" wire:navigate>New Survey</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search surveys..." icon="magnifying-glass" class="max-w-md" />

    <flux:table :paginate="$this->surveys">
        <flux:table.columns>
            <flux:table.column>Title</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Questions</flux:table.column>
            <flux:table.column>Responses</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->surveys as $s)
                <flux:table.row :key="$s->id">
                    <flux:table.cell variant="strong">{{ $s->title }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$statusColor[$s->status]" size="sm">{{ ucfirst($s->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $s->questions_count }}</flux:table.cell>
                    <flux:table.cell>{{ $s->responses_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="qr-code" :href="route('surveys.share', $s)" wire:navigate>Share</flux:button>
                        <flux:button size="sm" variant="ghost" icon="chart-bar" :href="route('surveys.responses', $s)" wire:navigate>Responses</flux:button>
                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('surveys.edit', $s)" wire:navigate />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $s->id }})" wire:confirm="Delete this survey and all responses?" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">No surveys yet. Create one.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

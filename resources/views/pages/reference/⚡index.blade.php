<?php

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\ImagingExam;
use App\Models\LabTest;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Reference Data')]
class extends Component
{
    public string $tab = 'barangays';

    public ?int $editingId = null;
    public string $name = '';
    public string $category = '';
    public string $unit = '';
    public string $reference_range = '';
    public string $modality = '';
    public bool $active = true;

    private function model(): string
    {
        return match ($this->tab) {
            'lab' => LabTest::class,
            'imaging' => ImagingExam::class,
            'assistance' => AssistanceType::class,
            default => Barangay::class,
        };
    }

    #[Computed]
    public function rows()
    {
        return $this->model()::orderBy('name')->get();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $row = $this->model()::findOrFail($id);
        $this->editingId = $row->id;
        $this->name = $row->name;
        $this->category = $row->category ?? '';
        $this->unit = $row->unit ?? '';
        $this->reference_range = $row->reference_range ?? '';
        $this->modality = $row->modality ?? '';
        $this->active = property_exists($row, 'active') || isset($row->active) ? (bool) ($row->active ?? true) : true;
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|string|max:255']);

        $payload = ['name' => $this->name];
        if ($this->tab === 'lab') {
            $payload += ['category' => $this->category ?: null, 'unit' => $this->unit ?: null, 'reference_range' => $this->reference_range ?: null, 'active' => $this->active];
        } elseif ($this->tab === 'imaging') {
            $payload += ['modality' => $this->modality ?: null, 'active' => $this->active];
        } elseif ($this->tab === 'assistance') {
            $payload += ['category' => $this->category ?: null, 'active' => $this->active];
        }

        $this->model()::updateOrCreate(['id' => $this->editingId], $payload);
        $this->resetForm();
        Flux::toast(text: 'Saved.', variant: 'success');
    }

    public function delete(int $id): void
    {
        $this->model()::findOrFail($id)->delete();
        Flux::toast(text: 'Deleted.', variant: 'success');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'category', 'unit', 'reference_range', 'modality']);
        $this->active = true;
        $this->resetValidation();
    }
};
?>

<div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-4 p-2">
    <flux:heading size="xl">Reference Data</flux:heading>

    <div class="flex flex-wrap gap-2">
        @foreach (['barangays' => 'Barangays', 'lab' => 'Lab Tests', 'imaging' => 'Imaging Exams', 'assistance' => 'Assistance Types'] as $key => $label)
            <flux:button size="sm" :variant="$tab === $key ? 'primary' : 'ghost'" wire:click="setTab('{{ $key }}')">{{ $label }}</flux:button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Add / edit form --}}
        <flux:card class="space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ $editingId ? 'Edit' : 'Add' }}</flux:heading>
            <flux:input wire:model="name" label="Name" />
            @error('name') <flux:text color="red">{{ $message }}</flux:text> @enderror
            @if ($tab === 'lab')
                <flux:input wire:model="category" label="Category" />
                <flux:input wire:model="unit" label="Unit" />
                <flux:input wire:model="reference_range" label="Reference range" />
            @elseif ($tab === 'imaging')
                <flux:input wire:model="modality" label="Modality" />
            @elseif ($tab === 'assistance')
                <flux:input wire:model="category" label="Category" />
            @endif
            @if (in_array($tab, ['lab', 'imaging', 'assistance']))
                <flux:checkbox wire:model="active" label="Active" />
            @endif
            <div class="flex gap-2">
                <flux:button variant="primary" wire:click="save">{{ $editingId ? 'Update' : 'Add' }}</flux:button>
                @if ($editingId)
                    <flux:button variant="ghost" wire:click="setTab('{{ $tab }}')">Cancel</flux:button>
                @endif
            </div>
        </flux:card>

        {{-- List --}}
        <flux:card class="lg:col-span-2">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->rows as $row)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ $row->name }}</span>
                            <flux:text size="sm">
                                {{ $row->category ?? '' }} {{ $row->modality ?? '' }} {{ $row->unit ? '· '.$row->unit : '' }}
                                @if (isset($row->active) && ! $row->active) · inactive @endif
                            </flux:text>
                        </div>
                        <div>
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="edit({{ $row->id }})" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $row->id }})" wire:confirm="Delete this entry?" />
                        </div>
                    </div>
                @empty
                    <flux:text class="py-2 text-zinc-500">No entries.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>

<?php

use App\Models\Medicine;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Medicine')]
class extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $generic_name = '';
    public string $form = '';
    public string $strength = '';
    public string $unit = 'pcs';
    public $reorder_level = 50;
    public bool $active = true;

    public function mount(?Medicine $medicine = null): void
    {
        if ($medicine && $medicine->exists) {
            $this->editingId = $medicine->id;
            $this->name = $medicine->name;
            $this->generic_name = $medicine->generic_name ?? '';
            $this->form = $medicine->form ?? '';
            $this->strength = $medicine->strength ?? '';
            $this->unit = $medicine->unit ?? 'pcs';
            $this->reorder_level = $medicine->reorder_level;
            $this->active = (bool) $medicine->active;
        }
    }

    public function save()
    {
        $data = $this->validate([
            'name'          => 'required|string|max:255',
            'generic_name'  => 'nullable|string|max:255',
            'form'          => 'nullable|string|max:100',
            'strength'      => 'nullable|string|max:100',
            'unit'          => 'nullable|string|max:50',
            'reorder_level' => 'required|integer|min:0',
            'active'        => 'boolean',
        ]);

        Medicine::updateOrCreate(['id' => $this->editingId], $data);
        Flux::toast(text: $this->editingId ? 'Medicine updated.' : 'Medicine added.', variant: 'success');
        return $this->redirect(route('pharmacy.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('pharmacy.index')" wire:navigate />
        <flux:heading size="xl">{{ $editingId ? 'Edit Medicine' : 'Add Medicine' }}</flux:heading>
    </div>

    <form wire:submit="save">
        <flux:card class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" label="Brand / name" required />
                <flux:input wire:model="generic_name" label="Generic name" />
                <flux:input wire:model="form" label="Form" placeholder="Tablet, Syrup..." />
                <flux:input wire:model="strength" label="Strength" placeholder="500 mg" />
                <flux:input wire:model="unit" label="Unit" placeholder="pcs, bottle" />
                <flux:input wire:model="reorder_level" label="Reorder level" type="number" />
            </div>
            <flux:checkbox wire:model="active" label="Active" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('pharmacy.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">{{ $editingId ? 'Update' : 'Add Medicine' }}</flux:button>
            </div>
        </flux:card>
    </form>
</div>

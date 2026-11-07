<?php

use App\Models\Medicine;
use App\Models\StockMovement;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Receive Stock')]
class extends Component
{
    public $medicine_id = '';
    public $quantity = '';
    public string $batch_no = '';
    public string $expiry_date = '';
    public $unit_cost = '';
    public string $reference = 'Delivery';

    public function mount(): void
    {
        if ($mid = request()->integer('medicine')) {
            $this->medicine_id = $mid;
        }
    }

    #[Computed]
    public function medicineList()
    {
        return Medicine::where('active', true)->orderBy('name')->get();
    }

    public function save()
    {
        $data = $this->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'quantity'    => 'required|integer|min:1',
            'batch_no'    => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
            'unit_cost'   => 'nullable|numeric|min:0',
            'reference'   => 'nullable|string|max:255',
        ]);

        StockMovement::create([
            'medicine_id' => $data['medicine_id'],
            'type'        => 'in',
            'quantity'    => $data['quantity'],
            'batch_no'    => $data['batch_no'] ?: null,
            'expiry_date' => $data['expiry_date'] ?: null,
            'unit_cost'   => $data['unit_cost'] !== '' ? $data['unit_cost'] : null,
            'reference'   => $data['reference'] ?: 'Delivery',
            'moved_at'    => now(),
            'user_id'     => auth()->id(),
        ]);

        Flux::toast(text: 'Stock received.', variant: 'success');
        return $this->redirect(route('pharmacy.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('pharmacy.index')" wire:navigate />
        <flux:heading size="xl">Receive Stock</flux:heading>
    </div>

    <form wire:submit="save">
        <flux:card class="space-y-4">
            <flux:select wire:model="medicine_id" label="Medicine" placeholder="Select medicine...">
                @foreach ($this->medicineList as $m)
                    <flux:select.option :value="$m->id">{{ $m->name }} {{ $m->strength }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('medicine_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="quantity" label="Quantity received" type="number" required />
                <flux:input wire:model="batch_no" label="Batch / Lot no" />
                <flux:input wire:model="expiry_date" label="Expiry date" type="date" />
                <flux:input wire:model="unit_cost" label="Unit cost" type="number" step="0.01" />
            </div>
            <flux:input wire:model="reference" label="Source / reference" placeholder="DOH delivery, LGU purchase..." />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('pharmacy.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Receive</flux:button>
            </div>
        </flux:card>
    </form>
</div>

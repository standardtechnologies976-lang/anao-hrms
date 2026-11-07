<?php

use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\Dispense;
use App\Models\Medicine;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Dispense')]
class extends Component
{
    public ?Dispense $dispense = null;
    public bool $viewing = false;

    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';
    public ?int $consultation_id = null;

    public array $items = [];
    public string $notes = '';

    public function mount(?Dispense $dispense = null): void
    {
        if ($dispense && $dispense->exists) {
            $dispense->loadMissing(['citizen', 'items.medicine']);
            $this->dispense = $dispense;
            $this->viewing = true;
            $this->citizenLabel = $dispense->citizen->full_name.' ('.$dispense->citizen->citizen_no.')';
            $this->notes = $dispense->notes ?? '';
            return;
        }

        if ($cid = request()->integer('consultation')) {
            $c = Consultation::with(['citizen', 'prescriptions'])->find($cid);
            if ($c) {
                $this->citizen_id = $c->citizen_id;
                $this->citizenLabel = $c->citizen->full_name.' ('.$c->citizen->citizen_no.')';
                $this->consultation_id = $c->id;
                foreach ($c->prescriptions as $rx) {
                    $match = Medicine::where('active', true)
                        ->where(fn ($q) => $q->where('name', 'like', "%{$rx->drug_name}%")->orWhere('generic_name', 'like', "%{$rx->drug_name}%"))
                        ->first();
                    $this->items[] = [
                        'medicine_id' => $match?->id ?? '',
                        'quantity' => $rx->quantity ?: '',
                        'instructions' => trim(collect([$rx->dosage, $rx->frequency, $rx->duration, $rx->instructions])->filter()->implode(' ')) ?: ($match ? '' : $rx->drug_name),
                    ];
                }
            }
        }

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    #[Computed]
    public function medicineList()
    {
        return Medicine::where('active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function stockMap(): array
    {
        return $this->medicineList->mapWithKeys(fn ($m) => [$m->id => $m->currentStock()])->toArray();
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }
        return Citizen::query()->search($this->citizenSearch)->orderBy('last_name')->limit(8)->get();
    }

    public function selectCitizen(int $id): void
    {
        $c = Citizen::findOrFail($id);
        $this->citizen_id = $c->id;
        $this->citizenLabel = $c->full_name.' ('.$c->citizen_no.')';
        $this->citizenSearch = '';
    }

    public function clearCitizen(): void
    {
        $this->reset(['citizen_id', 'citizenLabel', 'citizenSearch', 'consultation_id']);
    }

    public function addItem(): void
    {
        $this->items[] = ['medicine_id' => '', 'quantity' => '', 'instructions' => ''];
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function save()
    {
        $this->validate([
            'citizen_id'        => 'required|exists:citizens,id',
            'items'             => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ], [], [
            'items.*.medicine_id' => 'medicine',
            'items.*.quantity' => 'quantity',
        ]);

        // Overselling guard: sum requested per medicine vs stock
        $requested = [];
        foreach ($this->items as $it) {
            $requested[$it['medicine_id']] = ($requested[$it['medicine_id']] ?? 0) + (int) $it['quantity'];
        }
        foreach ($requested as $medId => $qty) {
            $available = $this->stockMap[$medId] ?? Medicine::find($medId)?->currentStock() ?? 0;
            if ($qty > $available) {
                $med = Medicine::find($medId);
                $this->addError('items', "Not enough stock for {$med->name} {$med->strength}: requested {$qty}, available {$available}.");
                return;
            }
        }

        DB::transaction(function () {
            $dispense = Dispense::create([
                'citizen_id'      => $this->citizen_id,
                'consultation_id' => $this->consultation_id,
                'dispensed_at'    => now(),
                'dispensed_by'    => auth()->id(),
                'notes'           => $this->notes ?: null,
            ]);

            foreach ($this->items as $it) {
                $dispense->items()->create([
                    'medicine_id' => $it['medicine_id'],
                    'quantity'    => (int) $it['quantity'],
                    'instructions' => $it['instructions'] ?: null,
                ]);
                StockMovement::create([
                    'medicine_id' => $it['medicine_id'],
                    'type'        => 'out',
                    'quantity'    => (int) $it['quantity'],
                    'reference'   => 'Dispense #'.$dispense->id,
                    'dispense_id' => $dispense->id,
                    'moved_at'    => now(),
                    'user_id'     => auth()->id(),
                ]);
            }
        });

        Flux::toast(text: 'Medicines dispensed.', variant: 'success');
        return $this->redirect(route('pharmacy.dispenses'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('pharmacy.dispenses')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $viewing ? 'Dispense Record' : 'New Dispense' }}</flux:heading>
            <flux:text class="mt-1">{{ $viewing ? $dispense->dispensed_at->format('M j, Y g:i A') : 'Issue medicines to a patient' }}</flux:text>
        </div>
    </div>

    @if ($viewing)
        <flux:card class="space-y-2">
            <flux:heading size="lg">Patient</flux:heading>
            <flux:text class="font-medium">{{ $citizenLabel }}</flux:text>
        </flux:card>
        <flux:card class="space-y-3">
            <flux:heading size="lg">Items Dispensed</flux:heading>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($dispense->items as $it)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <div class="font-medium">{{ $it->medicine->name }} {{ $it->medicine->strength }}</div>
                            @if ($it->instructions) <flux:text size="sm">{{ $it->instructions }}</flux:text> @endif
                        </div>
                        <flux:badge color="zinc">{{ $it->quantity }} {{ $it->medicine->unit }}</flux:badge>
                    </div>
                @endforeach
            </div>
            @if ($notes) <flux:text>{{ $notes }}</flux:text> @endif
        </flux:card>
    @else
        <form wire:submit="save" class="flex flex-col gap-6">
            <flux:card class="space-y-3">
                <flux:heading size="lg">Patient</flux:heading>
                @if ($citizen_id)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                        <span class="font-medium">{{ $citizenLabel }}</span>
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    </div>
                @else
                    <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search patient by name or number..." icon="magnifying-glass" />
                    @if ($this->citizenResults->isNotEmpty())
                        <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                            @foreach ($this->citizenResults as $c)
                                <button type="button" wire:click="selectCitizen({{ $c->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                    {{ $c->full_name }} <span class="text-zinc-500">- {{ $c->citizen_no }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
                @error('citizen_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
            </flux:card>

            <flux:card class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">Medicines</flux:heading>
                    <flux:button size="sm" variant="filled" icon="plus" wire:click="addItem" type="button">Add item</flux:button>
                </div>
                @error('items') <flux:text color="red">{{ $message }}</flux:text> @enderror
                @foreach ($items as $i => $it)
                    <div wire:key="di-{{ $i }}" class="grid grid-cols-1 gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-12 sm:items-end">
                        <flux:select wire:model="items.{{ $i }}.medicine_id" placeholder="Select medicine" class="sm:col-span-6">
                            @foreach ($this->medicineList as $m)
                                <flux:select.option :value="$m->id">{{ $m->name }} {{ $m->strength }} (stock: {{ $this->stockMap[$m->id] ?? 0 }})</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="items.{{ $i }}.quantity" type="number" placeholder="Qty" class="sm:col-span-2" />
                        <flux:input wire:model="items.{{ $i }}.instructions" placeholder="Sig / instructions" class="sm:col-span-3" />
                        <div class="flex items-end sm:col-span-1">
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeItem({{ $i }})" type="button" />
                        </div>
                    </div>
                @endforeach
                <flux:textarea wire:model="notes" label="Notes" rows="2" />
            </flux:card>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('pharmacy.dispenses')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Dispense</flux:button>
            </div>
        </form>
    @endif
</div>

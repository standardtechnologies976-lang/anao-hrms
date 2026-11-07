<?php

use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\LabOrder;
use App\Models\LabTest;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Lab Order')]
class extends Component
{
    public ?LabOrder $order = null;
    public ?int $editingId = null;
    public string $status = 'requested';

    // Patient picker
    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    public array $selectedTests = [];
    public string $remarks = '';

    // Results (edit): keyed by item id
    public array $results = [];

    public function mount(?LabOrder $order = null): void
    {
        if ($order && $order->exists) {
            $order->loadMissing(['citizen', 'items.test']);
            $this->order = $order;
            $this->editingId = $order->id;
            $this->status = $order->status;
            $this->citizen_id = $order->citizen_id;
            $this->citizenLabel = $order->citizen->full_name.' ('.$order->citizen->citizen_no.')';
            $this->remarks = $order->remarks ?? '';
            foreach ($order->items as $item) {
                $this->results[$item->id] = [
                    'result' => $item->result ?? '',
                    'flag' => $item->flag ?? '',
                    'remarks' => $item->remarks ?? '',
                ];
            }
        } elseif ($cid = request()->integer('consultation')) {
            $c = Consultation::with('citizen')->find($cid);
            if ($c) {
                $this->citizen_id = $c->citizen_id;
                $this->citizenLabel = $c->citizen->full_name.' ('.$c->citizen->citizen_no.')';
            }
        }
    }

    #[Computed]
    public function catalog()
    {
        return LabTest::where('active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');
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
        $this->reset(['citizen_id', 'citizenLabel', 'citizenSearch']);
    }

    public function save()
    {
        $this->validate([
            'citizen_id'      => 'required|exists:citizens,id',
            'selectedTests'   => 'required|array|min:1',
            'selectedTests.*' => 'exists:lab_tests,id',
            'remarks'         => 'nullable|string',
        ], [], ['selectedTests' => 'tests']);

        $order = LabOrder::create([
            'citizen_id' => $this->citizen_id,
            'ordered_by' => auth()->id(),
            'ordered_at' => now(),
            'status'     => 'requested',
            'remarks'    => $this->remarks ?: null,
        ]);

        foreach ($this->selectedTests as $testId) {
            $order->items()->create(['lab_test_id' => $testId]);
        }

        Flux::toast(text: 'Lab order created.', variant: 'success');
        return $this->redirect(route('laboratory.edit', $order), navigate: true);
    }

    public function saveResults(): void
    {
        foreach ($this->order->items as $item) {
            $r = $this->results[$item->id] ?? null;
            if ($r === null) {
                continue;
            }
            $item->update([
                'result'  => $r['result'] !== '' ? $r['result'] : null,
                'flag'    => $r['flag'] !== '' ? $r['flag'] : null,
                'remarks' => $r['remarks'] !== '' ? $r['remarks'] : null,
            ]);
        }

        $this->order->refresh()->loadMissing('items');
        $allDone = $this->order->items->every(fn ($i) => filled($i->result));
        $this->order->update(['status' => $allDone ? 'completed' : 'in_process']);
        $this->status = $this->order->status;

        Flux::toast(text: 'Results saved.', variant: 'success');
    }

    public function release()
    {
        $this->order->update(['status' => 'released', 'released_at' => now()]);
        Flux::toast(text: 'Results released.', variant: 'success');
        return $this->redirect(route('laboratory.index'), navigate: true);
    }
};
?>

@php $flagColor = ['normal' => 'green', 'low' => 'amber', 'high' => 'amber', 'abnormal' => 'red']; @endphp

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('laboratory.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Lab Order Results' : 'New Lab Order' }}</flux:heading>
            <flux:text class="mt-1">{{ $editingId ? str($status)->replace('_',' ')->title() : 'Select tests to request' }}</flux:text>
        </div>
    </div>

    @if (! $editingId)
        {{-- CREATE: pick patient + tests --}}
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
                <flux:heading size="lg">Tests</flux:heading>
                @error('selectedTests') <flux:text color="red">{{ $message }}</flux:text> @enderror
                @foreach ($this->catalog as $category => $tests)
                    <div>
                        <flux:text class="mb-2 font-medium">{{ $category ?: 'Other' }}</flux:text>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($tests as $test)
                                <flux:checkbox wire:model="selectedTests" value="{{ $test->id }}" label="{{ $test->name }}" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <flux:textarea wire:model="remarks" label="Remarks" rows="2" />
            </flux:card>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('laboratory.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create Order</flux:button>
            </div>
        </form>
    @else
        {{-- EDIT: encode results --}}
        <flux:card class="space-y-2">
            <flux:heading size="lg">Patient</flux:heading>
            <flux:text class="font-medium">{{ $citizenLabel }}</flux:text>
            @if ($remarks) <flux:text>{{ $remarks }}</flux:text> @endif
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Results</flux:heading>
            <div class="space-y-3">
                @foreach ($order->items as $item)
                    <div wire:key="item-{{ $item->id }}" class="grid grid-cols-1 gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-12 sm:items-end">
                        <div class="sm:col-span-4">
                            <flux:text class="font-medium">{{ $item->test->name }}</flux:text>
                            <flux:text size="sm">{{ $item->test->reference_range ? 'Ref: '.$item->test->reference_range : '' }} {{ $item->test->unit ? '('.$item->test->unit.')' : '' }}</flux:text>
                        </div>
                        <flux:input wire:model="results.{{ $item->id }}.result" placeholder="Result" class="sm:col-span-3" :disabled="$status === 'released'" />
                        <flux:select wire:model="results.{{ $item->id }}.flag" placeholder="Flag" class="sm:col-span-2" :disabled="$status === 'released'">
                            <flux:select.option value="">-</flux:select.option>
                            <flux:select.option value="normal">Normal</flux:select.option>
                            <flux:select.option value="low">Low</flux:select.option>
                            <flux:select.option value="high">High</flux:select.option>
                            <flux:select.option value="abnormal">Abnormal</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="results.{{ $item->id }}.remarks" placeholder="Remarks" class="sm:col-span-3" :disabled="$status === 'released'" />
                    </div>
                @endforeach
            </div>

            @if ($status !== 'released')
                <div class="flex justify-between">
                    <flux:button variant="primary" wire:click="saveResults">Save Results</flux:button>
                    <flux:button variant="filled" icon="check-circle" wire:click="release" wire:confirm="Release these results? They become read-only.">Release Results</flux:button>
                </div>
            @else
                <flux:text class="text-zinc-500">Released {{ $order->released_at?->format('M j, Y g:i A') }}. Results are final.</flux:text>
            @endif
        </flux:card>
    @endif
</div>

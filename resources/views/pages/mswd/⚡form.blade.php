<?php

use App\Models\AssistanceRequest;
use App\Models\AssistanceType;
use App\Models\Citizen;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Assistance Request')]
class extends Component
{
    public ?int $editingId = null;
    public ?string $referenceNo = null;

    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    public $assistance_type_id = '';
    public string $request_date = '';
    public string $purpose = '';
    public $amount_requested = '';
    public $amount_granted = '';
    public string $status = 'pending';
    public string $remarks = '';

    public function mount(?AssistanceRequest $assistance = null): void
    {
        if ($assistance && $assistance->exists) {
            $assistance->loadMissing('citizen');
            $this->editingId = $assistance->id;
            $this->referenceNo = $assistance->reference_no;
            $this->citizen_id = $assistance->citizen_id;
            $this->citizenLabel = $assistance->citizen->full_name.' ('.$assistance->citizen->citizen_no.')';
            $this->assistance_type_id = $assistance->assistance_type_id;
            $this->request_date = $assistance->request_date->format('Y-m-d');
            $this->purpose = $assistance->purpose ?? '';
            $this->amount_requested = $assistance->amount_requested ?? '';
            $this->amount_granted = $assistance->amount_granted ?? '';
            $this->status = $assistance->status;
            $this->remarks = $assistance->remarks ?? '';
        } else {
            $this->request_date = now()->toDateString();
        }
    }

    #[Computed]
    public function types()
    {
        return AssistanceType::where('active', true)->orderBy('name')->get();
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
        $data = $this->validate([
            'citizen_id'         => 'required|exists:citizens,id',
            'assistance_type_id' => 'required|exists:assistance_types,id',
            'request_date'       => 'required|date',
            'purpose'            => 'nullable|string',
            'amount_requested'   => 'nullable|numeric|min:0',
            'amount_granted'     => 'nullable|numeric|min:0',
            'status'             => 'required|in:pending,approved,released,denied',
            'remarks'            => 'nullable|string',
        ]);

        $data['amount_requested'] = $data['amount_requested'] !== '' ? $data['amount_requested'] : null;
        $data['amount_granted'] = $data['amount_granted'] !== '' ? $data['amount_granted'] : null;
        $data['processed_by'] = auth()->id();

        if ($this->status === 'released') {
            $existing = $this->editingId ? AssistanceRequest::find($this->editingId) : null;
            $data['released_at'] = $existing?->released_at ?? now();
        }

        AssistanceRequest::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast(text: $this->editingId ? 'Request updated.' : 'Request filed.', variant: 'success');
        return $this->redirect(route('mswd.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('mswd.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Process Assistance Request' : 'New Assistance Request' }}</flux:heading>
            <flux:text class="mt-1">{{ $editingId ? $referenceNo : 'File a social services assistance request' }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-3">
            <flux:heading size="lg">Citizen</flux:heading>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    @unless ($editingId)
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    @endunless
                </div>
            @else
                <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search citizen by name or number..." icon="magnifying-glass" />
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
            <flux:heading size="lg">Request Details</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="assistance_type_id" label="Assistance type" placeholder="Select type...">
                    @foreach ($this->types as $t)
                        <flux:select.option :value="$t->id">{{ $t->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="request_date" label="Request date" type="date" />
                <flux:input wire:model="amount_requested" label="Amount requested (PHP)" type="number" step="0.01" />
                <flux:input wire:model="amount_granted" label="Amount granted (PHP)" type="number" step="0.01" />
            </div>
            @error('assistance_type_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:textarea wire:model="purpose" label="Purpose / circumstances" rows="3" />
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Status</flux:heading>
            <flux:radio.group wire:model="status" variant="segmented">
                <flux:radio value="pending" label="Pending" />
                <flux:radio value="approved" label="Approved" />
                <flux:radio value="released" label="Released" />
                <flux:radio value="denied" label="Denied" />
            </flux:radio.group>
            <flux:textarea wire:model="remarks" label="Remarks" rows="2" />
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('mswd.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Save' : 'File Request' }}</flux:button>
        </div>
    </form>
</div>

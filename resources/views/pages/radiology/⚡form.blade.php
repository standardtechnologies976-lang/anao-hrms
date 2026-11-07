<?php

use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\ImagingExam;
use App\Models\RadiologyOrder;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Imaging Request')]
class extends Component
{
    public ?RadiologyOrder $order = null;
    public ?int $editingId = null;
    public string $status = 'requested';

    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';

    public $imaging_exam_id = '';
    public string $clinical_indication = '';

    // Report
    public string $findings = '';
    public string $impression = '';

    public function mount(?RadiologyOrder $order = null): void
    {
        if ($order && $order->exists) {
            $order->loadMissing(['citizen', 'exam']);
            $this->order = $order;
            $this->editingId = $order->id;
            $this->status = $order->status;
            $this->citizen_id = $order->citizen_id;
            $this->citizenLabel = $order->citizen->full_name.' ('.$order->citizen->citizen_no.')';
            $this->imaging_exam_id = $order->imaging_exam_id;
            $this->clinical_indication = $order->clinical_indication ?? '';
            $this->findings = $order->findings ?? '';
            $this->impression = $order->impression ?? '';
        } elseif ($cid = request()->integer('consultation')) {
            $c = Consultation::with('citizen')->find($cid);
            if ($c) {
                $this->citizen_id = $c->citizen_id;
                $this->citizenLabel = $c->citizen->full_name.' ('.$c->citizen->citizen_no.')';
            }
        }
    }

    #[Computed]
    public function exams()
    {
        return ImagingExam::where('active', true)->orderBy('modality')->orderBy('name')->get();
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
            'citizen_id'          => 'required|exists:citizens,id',
            'imaging_exam_id'     => 'required|exists:imaging_exams,id',
            'clinical_indication' => 'nullable|string',
        ]);

        if ($this->editingId) {
            $this->order->update($data);
            Flux::toast(text: 'Request updated.', variant: 'success');
            return;
        }

        $order = RadiologyOrder::create($data + [
            'ordered_by' => auth()->id(),
            'ordered_at' => now(),
            'status'     => 'requested',
        ]);

        Flux::toast(text: 'Imaging request created.', variant: 'success');
        return $this->redirect(route('radiology.edit', $order), navigate: true);
    }

    public function markPerformed(): void
    {
        $this->order->update(['status' => 'performed', 'performed_at' => now()]);
        $this->status = 'performed';
        Flux::toast(text: 'Marked as performed.', variant: 'success');
    }

    public function saveReport(): void
    {
        $this->validate([
            'findings'   => 'nullable|string',
            'impression' => 'nullable|string',
        ]);

        $this->order->update([
            'findings'    => $this->findings ?: null,
            'impression'  => $this->impression ?: null,
            'status'      => 'reported',
            'reported_by' => auth()->id(),
            'performed_at' => $this->order->performed_at ?? now(),
        ]);
        $this->status = 'reported';
        Flux::toast(text: 'Report saved.', variant: 'success');
    }

    public function release()
    {
        $this->order->update(['status' => 'released', 'released_at' => now()]);
        Flux::toast(text: 'Report released.', variant: 'success');
        return $this->redirect(route('radiology.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('radiology.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Imaging Report' : 'New Imaging Request' }}</flux:heading>
            <flux:text class="mt-1">{{ $editingId ? ucfirst($status) : 'Select an exam' }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-3">
            <flux:heading size="lg">Patient</flux:heading>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    @unless ($editingId)
                        <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                    @endunless
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
            <flux:heading size="lg">Exam</flux:heading>
            <flux:select wire:model="imaging_exam_id" label="Imaging exam" placeholder="Select exam..." :disabled="$status === 'released'">
                @foreach ($this->exams as $exam)
                    <flux:select.option :value="$exam->id">{{ $exam->modality }} - {{ $exam->name }}</flux:select.option>
                @endforeach
            </flux:select>
            @error('imaging_exam_id') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:textarea wire:model="clinical_indication" label="Clinical indication" rows="2" placeholder="Reason for exam" :disabled="$status === 'released'" />
            @if ($status !== 'released')
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ $editingId ? 'Save Request' : 'Create Request' }}</flux:button>
                </div>
            @endif
        </flux:card>
    </form>

    @if ($editingId)
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Radiology Report</flux:heading>
                @if ($status === 'requested')
                    <flux:button size="sm" variant="filled" icon="camera" wire:click="markPerformed">Mark Performed</flux:button>
                @endif
            </div>
            <flux:textarea wire:model="findings" label="Findings" rows="5" :disabled="$status === 'released'" />
            <flux:textarea wire:model="impression" label="Impression" rows="3" :disabled="$status === 'released'" />
            @if ($status !== 'released')
                <div class="flex justify-between">
                    <flux:button variant="primary" wire:click="saveReport">Save Report</flux:button>
                    <flux:button variant="filled" icon="check-circle" wire:click="release" wire:confirm="Release this report? It becomes read-only.">Release Report</flux:button>
                </div>
            @else
                <flux:text class="text-zinc-500">Released {{ $order->released_at?->format('M j, Y g:i A') }}. Report is final.</flux:text>
            @endif
        </flux:card>
    @endif
</div>

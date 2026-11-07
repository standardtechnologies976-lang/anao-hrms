<?php

use App\Models\Appointment;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Manage Appointment')]
class extends Component
{
    public Appointment $appointment;
    public string $service = '';
    public string $preferred_date = '';
    public string $preferred_time = '';
    public string $status = 'pending';
    public string $scheduled_at = '';
    public string $remarks = '';

    public function mount(Appointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        $this->appointment = $appointment;
        $this->service = $appointment->service;
        $this->preferred_date = $appointment->preferred_date->format('Y-m-d');
        $this->preferred_time = $appointment->preferred_time ?? '';
        $this->status = $appointment->status;
        $this->scheduled_at = $appointment->scheduled_at?->format('Y-m-d\TH:i') ?? '';
        $this->remarks = $appointment->remarks ?? '';
    }

    public function save()
    {
        $data = $this->validate([
            'status'       => 'required|in:pending,confirmed,completed,cancelled',
            'scheduled_at' => 'nullable|date',
            'remarks'      => 'nullable|string',
        ]);

        $this->appointment->update([
            'status'       => $data['status'],
            'scheduled_at' => $data['scheduled_at'] ?: null,
            'remarks'      => $data['remarks'] ?: null,
        ]);

        Flux::toast(text: 'Appointment updated.', variant: 'success');
        return $this->redirect(route('appointments.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('appointments.index')" wire:navigate />
        <div>
            <flux:heading size="xl">Manage Appointment</flux:heading>
            <flux:text class="mt-1">{{ $appointment->reference_no }}</flux:text>
        </div>
    </div>

    <flux:card class="space-y-2">
        <flux:heading size="lg">{{ $appointment->citizen->full_name }}</flux:heading>
        <flux:text>{{ $appointment->citizen->citizen_no }} · {{ $appointment->citizen->contact_no ?: 'no contact' }}</flux:text>
        <flux:text>Requested: {{ $service }} on {{ $preferred_date }} {{ $preferred_time }}</flux:text>
        @if ($appointment->reason) <flux:text>Reason: {{ $appointment->reason }}</flux:text> @endif
    </flux:card>

    <form wire:submit="save">
        <flux:card class="space-y-4">
            <flux:radio.group wire:model="status" label="Status" variant="segmented">
                <flux:radio value="pending" label="Pending" />
                <flux:radio value="confirmed" label="Confirmed" />
                <flux:radio value="completed" label="Completed" />
                <flux:radio value="cancelled" label="Cancelled" />
            </flux:radio.group>
            <flux:input wire:model="scheduled_at" label="Scheduled date/time" type="datetime-local" />
            <flux:textarea wire:model="remarks" label="Remarks to patient" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('appointments.index')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </flux:card>
    </form>
</div>

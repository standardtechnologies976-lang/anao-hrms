<?php

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::portal')]
#[Title('Book Appointment')]
class extends Component
{
    public string $service = '';
    public string $preferred_date = '';
    public string $preferred_time = '';
    public string $reason = '';

    public function mount(): void
    {
        if (! Auth::guard('citizen')->check()) {
            $this->redirect(route('portal.login'), navigate: true);
            return;
        }
        if (! Auth::guard('citizen')->user()->isVerified()) {
            $this->redirect(route('portal.dashboard'), navigate: true);
        }
    }

    public function book()
    {
        $data = $this->validate([
            'service'        => 'required|string|max:255',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'nullable|string|max:50',
            'reason'         => 'nullable|string',
        ]);

        Appointment::create($data + [
            'citizen_id' => Auth::guard('citizen')->id(),
            'status'     => 'pending',
            'source'     => 'portal',
        ]);

        Flux::toast(text: 'Appointment request submitted.', variant: 'success');
        return $this->redirect(route('portal.dashboard'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-lg">
    <div class="mb-4 flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('portal.dashboard')" wire:navigate />
        <flux:heading size="xl">Book an Appointment</flux:heading>
    </div>

    <form wire:submit="book">
        <flux:card class="space-y-4">
            <flux:select wire:model="service" label="Service" placeholder="Select service...">
                <flux:select.option value="General Consultation">General Consultation</flux:select.option>
                <flux:select.option value="Prenatal Checkup">Prenatal Checkup</flux:select.option>
                <flux:select.option value="Immunization">Immunization</flux:select.option>
                <flux:select.option value="Family Planning">Family Planning</flux:select.option>
                <flux:select.option value="Dental">Dental</flux:select.option>
                <flux:select.option value="Laboratory">Laboratory</flux:select.option>
                <flux:select.option value="Medical Certificate">Medical Certificate</flux:select.option>
                <flux:select.option value="Other">Other</flux:select.option>
            </flux:select>
            @error('service') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="preferred_date" label="Preferred date" type="date" />
                <flux:input wire:model="preferred_time" label="Preferred time" placeholder="e.g. 9:00 AM" />
            </div>
            @error('preferred_date') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:textarea wire:model="reason" label="Reason (optional)" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" :href="route('portal.dashboard')" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Submit Request</flux:button>
            </div>
        </flux:card>
    </form>
</div>

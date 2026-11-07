<?php

use App\Models\CitizenIdDocument;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::portal')]
#[Title('My Records')]
class extends Component
{
    use WithFileUploads;

    public string $id_type = '';
    public string $id_number = '';
    public $idFile;

    public function mount(): void
    {
        if (! Auth::guard('citizen')->check()) {
            $this->redirect(route('portal.login'), navigate: true);
        }
    }

    #[Computed]
    public function citizen()
    {
        return Auth::guard('citizen')->user();
    }

    #[Computed]
    public function documents()
    {
        return $this->citizen->idDocuments()->get();
    }

    #[Computed]
    public function appointments()
    {
        return $this->citizen->appointments()->limit(10)->get();
    }

    #[Computed]
    public function visits()
    {
        return $this->citizen->consultations()->with('physician')->limit(10)->get();
    }

    #[Computed]
    public function labResults()
    {
        return $this->citizen->labOrders()->where('status', 'released')->with('items.test')->limit(10)->get();
    }

    #[Computed]
    public function imaging()
    {
        return $this->citizen->radiologyOrders()->where('status', 'released')->with('exam')->limit(10)->get();
    }

    public function submitId()
    {
        $data = $this->validate([
            'id_type'   => 'required|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'idFile'    => 'required|image|max:5120',
        ]);

        $path = $this->idFile->store('citizen_ids', 'public');

        CitizenIdDocument::create([
            'citizen_id' => $this->citizen->id,
            'id_type'    => $data['id_type'],
            'id_number'  => $data['id_number'] ?: null,
            'file_path'  => $path,
            'status'     => 'pending',
        ]);

        // If previously flagged, move back to pending for re-review
        if ($this->citizen->isFlagged()) {
            $this->citizen->update(['verification_status' => 'pending', 'verification_remarks' => null]);
        }

        $this->reset(['id_type', 'id_number', 'idFile']);
        unset($this->documents);
        Flux::toast(text: 'ID submitted. A barangay health worker will review it.', variant: 'success');
    }
};
?>

@php
    $apptColor = ['pending' => 'amber', 'confirmed' => 'blue', 'completed' => 'green', 'cancelled' => 'red'];
    $flagColor = ['normal' => 'green', 'low' => 'amber', 'high' => 'amber', 'abnormal' => 'red'];
    $docColor = ['pending' => 'amber', 'accepted' => 'green', 'rejected' => 'red'];
@endphp

<div class="flex flex-col gap-6">
    {{-- Profile --}}
    <flux:card class="space-y-2">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">{{ $this->citizen->full_name }}</flux:heading>
            @if ($this->citizen->isVerified())
                <flux:badge color="green" icon="check-badge">Verified</flux:badge>
            @elseif ($this->citizen->isFlagged())
                <flux:badge color="red">Flagged</flux:badge>
            @else
                <flux:badge color="amber">Pending verification</flux:badge>
            @endif
        </div>
        <flux:text>
            {{ $this->citizen->sex ? ucfirst($this->citizen->sex) : '' }}
            @if ($this->citizen->age) · {{ $this->citizen->age }} yrs @endif
            @if ($this->citizen->barangay) · {{ $this->citizen->barangay->name }} @endif
        </flux:text>
    </flux:card>

    @if (! $this->citizen->isVerified())
        {{-- VERIFICATION GATE --}}
        @if ($this->citizen->isFlagged())
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>Your submission was flagged</flux:callout.heading>
                <flux:callout.text>{{ $this->citizen->verification_remarks ?: 'Please submit a clearer valid government ID.' }}</flux:callout.text>
            </flux:callout>
        @else
            <flux:callout variant="warning" icon="clock">
                <flux:callout.heading>Account pending verification</flux:callout.heading>
                <flux:callout.text>Submit a valid government ID below. A barangay health worker will review it before your health records are unlocked.</flux:callout.text>
            </flux:callout>
        @endif

        <flux:card class="space-y-4">
            <flux:heading size="lg">Submit a Valid Government ID</flux:heading>
            <form wire:submit="submitId" class="space-y-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <flux:select wire:model="id_type" label="ID type" placeholder="Select...">
                        <flux:select.option value="PhilSys (National ID)">PhilSys (National ID)</flux:select.option>
                        <flux:select.option value="PhilHealth ID">PhilHealth ID</flux:select.option>
                        <flux:select.option value="Driver's License">Driver's License</flux:select.option>
                        <flux:select.option value="Voter's ID">Voter's ID</flux:select.option>
                        <flux:select.option value="Postal ID">Postal ID</flux:select.option>
                        <flux:select.option value="Senior Citizen ID">Senior Citizen ID</flux:select.option>
                        <flux:select.option value="PWD ID">PWD ID</flux:select.option>
                        <flux:select.option value="Barangay ID">Barangay ID</flux:select.option>
                        <flux:select.option value="Other">Other</flux:select.option>
                    </flux:select>
                    <flux:input wire:model="id_number" label="ID number (optional)" />
                </div>
                <flux:input type="file" wire:model="idFile" label="Photo of your ID" accept="image/*" />
                @error('idFile') <flux:text color="red">{{ $message }}</flux:text> @enderror
                @error('id_type') <flux:text color="red">{{ $message }}</flux:text> @enderror
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submitId,idFile">Submit ID</span>
                    <span wire:loading wire:target="submitId,idFile">Uploading...</span>
                </flux:button>
            </form>

            @if ($this->documents->isNotEmpty())
                <flux:separator text="Submitted" />
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->documents as $doc)
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <span class="font-medium">{{ $doc->id_type }}</span>
                                <flux:text size="sm" class="block">{{ $doc->created_at->format('M j, Y g:i A') }}</flux:text>
                            </div>
                            <flux:badge :color="$docColor[$doc->status]" size="sm">{{ ucfirst($doc->status) }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>
    @else
        {{-- VERIFIED: full records --}}
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">My Appointments</flux:heading>
                <flux:button size="sm" variant="primary" icon="plus" :href="route('portal.book')" wire:navigate>Book</flux:button>
            </div>
            @forelse ($this->appointments as $a)
                <div class="flex items-center justify-between border-t border-zinc-100 py-2 dark:border-zinc-800">
                    <div>
                        <div class="font-medium">{{ $a->service }}</div>
                        <flux:text size="sm">{{ $a->preferred_date->format('M j, Y') }} {{ $a->preferred_time ? '· '.$a->preferred_time : '' }} · {{ $a->reference_no }}</flux:text>
                    </div>
                    <flux:badge :color="$apptColor[$a->status]" size="sm">{{ ucfirst($a->status) }}</flux:badge>
                </div>
            @empty
                <flux:text class="text-zinc-500">No appointments yet.</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">Visit History</flux:heading>
            @forelse ($this->visits as $v)
                <div class="border-t border-zinc-100 py-2 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $v->consulted_at->format('M j, Y') }}</span>
                        <flux:text size="sm">{{ $v->physician?->name }}</flux:text>
                    </div>
                    @if ($v->diagnosis) <flux:text size="sm">Diagnosis: {{ $v->diagnosis }}</flux:text> @endif
                </div>
            @empty
                <flux:text class="text-zinc-500">No visit records yet.</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">Laboratory Results</flux:heading>
            @forelse ($this->labResults as $order)
                <div class="border-t border-zinc-100 py-2 dark:border-zinc-800">
                    <flux:text size="sm" class="font-medium">{{ $order->ordered_at->format('M j, Y') }}</flux:text>
                    <div class="mt-1 space-y-1">
                        @foreach ($order->items as $item)
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ $item->test->name }}</span>
                                <span class="flex items-center gap-2">
                                    <span>{{ $item->result ?: '-' }} {{ $item->test->unit }}</span>
                                    @if ($item->flag) <flux:badge :color="$flagColor[$item->flag] ?? 'zinc'" size="sm">{{ ucfirst($item->flag) }}</flux:badge> @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <flux:text class="text-zinc-500">No released lab results.</flux:text>
            @endforelse
        </flux:card>

        <flux:card class="space-y-3">
            <flux:heading size="lg">Imaging Reports</flux:heading>
            @forelse ($this->imaging as $r)
                <div class="border-t border-zinc-100 py-2 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $r->exam->name }}</span>
                        <flux:text size="sm">{{ $r->ordered_at->format('M j, Y') }}</flux:text>
                    </div>
                    @if ($r->impression) <flux:text size="sm">Impression: {{ $r->impression }}</flux:text> @endif
                </div>
            @empty
                <flux:text class="text-zinc-500">No released imaging reports.</flux:text>
            @endforelse
        </flux:card>
    @endif
</div>

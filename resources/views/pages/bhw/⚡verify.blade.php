<?php

use App\Models\Citizen;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::bhw')]
#[Title('Review Account')]
class extends Component
{
    public Citizen $citizen;
    public string $remarks = '';

    public function mount(Citizen $citizen): void
    {
        $this->citizen = $citizen->load('barangay', 'idDocuments');
    }

    #[Computed]
    public function latestDoc()
    {
        return $this->citizen->idDocuments->firstWhere('status', 'pending') ?? $this->citizen->idDocuments->first();
    }

    #[Computed]
    public function idNumberMatches()
    {
        $doc = $this->latestDoc;
        if (! $doc || ! $doc->id_number) {
            return collect();
        }
        return \App\Models\CitizenIdDocument::where('id_number', $doc->id_number)
            ->where('citizen_id', '!=', $this->citizen->id)
            ->with('citizen')
            ->get();
    }

    #[Computed]
    public function personMatches()
    {
        return Citizen::where('id', '!=', $this->citizen->id)
            ->where('last_name', $this->citizen->last_name)
            ->where('first_name', $this->citizen->first_name)
            ->when($this->citizen->birth_date, fn ($q) => $q->whereDate('birth_date', $this->citizen->birth_date))
            ->get(['id', 'citizen_no', 'verification_status']);
    }

    public function accept()
    {
        $this->citizen->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'verification_remarks' => null,
        ]);
        if ($doc = $this->latestDoc) {
            $doc->update(['status' => 'accepted', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        }
        Flux::toast(text: 'Account verified.', variant: 'success');
        return $this->redirect(route('bhw-portal.verifications'), navigate: true);
    }

    public function flag()
    {
        $this->validate(['remarks' => 'required|string|min:3']);
        $this->citizen->update([
            'verification_status' => 'flagged',
            'verified_by' => auth()->id(),
            'verification_remarks' => $this->remarks,
        ]);
        if ($doc = $this->latestDoc) {
            $doc->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'remarks' => $this->remarks]);
        }
        Flux::toast(text: 'Account flagged.', variant: 'success');
        return $this->redirect(route('bhw-portal.verifications'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-4">
    <div class="flex items-center gap-2">
        <flux:button icon="arrow-left" variant="ghost" :href="route('bhw-portal.verifications')" wire:navigate />
        <flux:heading size="lg">Review Account</flux:heading>
    </div>

    {{-- Details the citizen typed --}}
    <flux:card class="space-y-1">
        <flux:heading size="lg">{{ $citizen->full_name }}</flux:heading>
        <flux:text size="sm">{{ $citizen->sex ? ucfirst($citizen->sex) : '' }} @if ($citizen->age) · {{ $citizen->age }} yrs @endif</flux:text>
        <flux:text size="sm">{{ $citizen->barangay?->name }} {{ $citizen->purok ? '· '.$citizen->purok : '' }}</flux:text>
        <flux:text size="sm">{{ $citizen->contact_no ?: 'no contact' }} · {{ $citizen->portal_email }}</flux:text>
    </flux:card>

    {{-- Duplicate warnings --}}
    @if ($this->idNumberMatches->isNotEmpty() || $this->personMatches->isNotEmpty())
        <flux:callout variant="warning" icon="exclamation-triangle">
            <flux:callout.heading>Possible duplicate detected</flux:callout.heading>
            <flux:callout.text>
                @if ($this->idNumberMatches->isNotEmpty())
                    This ID number is already used by {{ $this->idNumberMatches->count() }} other record(s).
                @endif
                @if ($this->personMatches->isNotEmpty())
                    A resident with the same name and birthdate already exists ({{ $this->personMatches->pluck('citizen_no')->filter()->implode(', ') }}).
                @endif
            </flux:callout.text>
        </flux:callout>
    @endif

    {{-- Submitted ID --}}
    <flux:card class="space-y-3">
        <flux:heading size="lg">Submitted ID</flux:heading>
        @if ($this->latestDoc)
            <flux:text size="sm">{{ $this->latestDoc->id_type }} @if ($this->latestDoc->id_number) · {{ $this->latestDoc->id_number }} @endif</flux:text>
            <a href="{{ $this->latestDoc->url() }}" target="_blank">
                <img src="{{ $this->latestDoc->url() }}" alt="Submitted ID" class="max-h-80 w-full rounded-lg border border-zinc-200 object-contain dark:border-zinc-700" />
            </a>
            <flux:text size="sm" class="text-zinc-500">Tap the image to open full size. Compare the name, photo, and birthdate against the details above.</flux:text>
        @else
            <flux:text class="text-zinc-500">No ID submitted yet.</flux:text>
        @endif
    </flux:card>

    {{-- Decision --}}
    <flux:card class="space-y-3">
        <flux:heading size="lg">Decision</flux:heading>
        <flux:textarea wire:model="remarks" label="Remarks (required to flag)" rows="2" placeholder="e.g. blurry photo, details do not match, suspected fake" />
        @error('remarks') <flux:text color="red">{{ $message }}</flux:text> @enderror
        <div class="flex gap-2">
            <flux:button variant="danger" icon="flag" wire:click="flag" class="flex-1">Flag as fake</flux:button>
            <flux:button variant="primary" icon="check-badge" wire:click="accept" wire:confirm="Verify this account? The resident will get full portal access." class="flex-1">Verify</flux:button>
        </div>
    </flux:card>
</div>

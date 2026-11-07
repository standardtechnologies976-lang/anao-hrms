<?php

use App\Models\Citizen;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::bhw')]
#[Title('Verify Residents')]
class extends Component
{
    #[Computed]
    public function pending()
    {
        $bx = auth()->user()->barangay_id;
        return Citizen::query()
            ->where('verification_status', 'pending')
            ->where('self_registered', true)
            ->whereHas('idDocuments', fn ($q) => $q->where('status', 'pending'))
            ->when($bx, fn ($q) => $q->where('barangay_id', $bx))
            ->with('barangay')
            ->orderBy('created_at')
            ->get();
    }
};
?>

<div class="flex flex-col gap-3">
    <div>
        <flux:heading size="lg">Verify Residents</flux:heading>
        <flux:text>Self-registered accounts waiting for ID verification.</flux:text>
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($this->pending as $c)
            <flux:card class="flex items-center justify-between">
                <div>
                    <div class="font-medium">{{ $c->full_name }}</div>
                    <flux:text size="sm">{{ $c->barangay?->name }} @if ($c->age) · {{ $c->age }} yrs @endif</flux:text>
                </div>
                <flux:button size="sm" variant="primary" icon="identification" :href="route('bhw-portal.verify', $c)" wire:navigate>Review</flux:button>
            </flux:card>
        @empty
            <flux:text class="text-zinc-500">No accounts waiting for verification.</flux:text>
        @endforelse
    </div>
</div>

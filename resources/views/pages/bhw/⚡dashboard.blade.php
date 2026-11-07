<?php

use App\Models\Citizen;
use App\Models\FieldVisit;
use App\Models\Household;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::bhw')]
#[Title('BHW Home')]
class extends Component
{
    private function barangayId(): ?int
    {
        return auth()->user()->barangay_id;
    }

    #[Computed]
    public function stats(): array
    {
        $bx = $this->barangayId();
        return [
            'residents' => Citizen::when($bx, fn ($q) => $q->where('barangay_id', $bx))->count(),
            'households' => Household::when($bx, fn ($q) => $q->where('barangay_id', $bx))->count(),
            'visits_month' => FieldVisit::where('bhw_id', auth()->id())->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->count(),
            'pregnant' => Citizen::when($bx, fn ($q) => $q->where('barangay_id', $bx))->where('is_pregnant', true)->count(),
        ];
    }

    #[Computed]
    public function recentVisits()
    {
        return FieldVisit::where('bhw_id', auth()->id())->with('citizen')->orderByDesc('visit_date')->limit(5)->get();
    }

    #[Computed]
    public function pendingVerifications(): int
    {
        $bx = auth()->user()->barangay_id;
        return \App\Models\Citizen::where('verification_status', 'pending')
            ->where('self_registered', true)
            ->whereHas('idDocuments', fn ($q) => $q->where('status', 'pending'))
            ->when($bx, fn ($q) => $q->where('barangay_id', $bx))
            ->count();
    }
};
?>

<div class="flex flex-col gap-4">
    <div>
        <flux:heading size="lg">Kumusta, {{ auth()->user()->name }}</flux:heading>
        <flux:text>Your barangay at a glance</flux:text>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <flux:card class="text-center">
            <div class="text-2xl font-bold">{{ $this->stats['residents'] }}</div>
            <flux:text size="sm">Residents</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <div class="text-2xl font-bold">{{ $this->stats['households'] }}</div>
            <flux:text size="sm">Households</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <div class="text-2xl font-bold">{{ $this->stats['visits_month'] }}</div>
            <flux:text size="sm">My visits this month</flux:text>
        </flux:card>
        <flux:card class="text-center">
            <div class="text-2xl font-bold text-pink-600">{{ $this->stats['pregnant'] }}</div>
            <flux:text size="sm">Pregnant to follow up</flux:text>
        </flux:card>
    </div>

    <flux:button variant="primary" icon="plus" :href="route('bhw-portal.visit.create')" wire:navigate class="w-full">Log a Field Visit</flux:button>

    <flux:button icon="clipboard-document-check" :href="route('bhw-portal.surveys')" wire:navigate class="w-full">Field Surveys</flux:button>

    <flux:button icon="identification" :href="route('bhw-portal.verifications')" wire:navigate class="w-full">
        Verify Residents
        @if ($this->pendingVerifications > 0)
            <flux:badge color="amber" size="sm" class="ml-2">{{ $this->pendingVerifications }}</flux:badge>
        @endif
    </flux:button>

    <flux:card class="space-y-2">
        <flux:heading size="lg">Recent Visits</flux:heading>
        @forelse ($this->recentVisits as $v)
            <div class="border-t border-zinc-100 py-2 dark:border-zinc-800">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ $v->citizen?->full_name ?? 'Household visit' }}</span>
                    <flux:text size="sm">{{ $v->visit_date->format('M j') }}</flux:text>
                </div>
                <flux:text size="sm">{{ $v->visit_type }} @if ($v->referred) · referred @endif</flux:text>
            </div>
        @empty
            <flux:text class="text-zinc-500">No visits logged yet.</flux:text>
        @endforelse
    </flux:card>
</div>

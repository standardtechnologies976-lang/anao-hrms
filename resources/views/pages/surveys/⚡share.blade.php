<?php

use App\Models\Survey;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('layouts::app')]
#[Title('Share Survey')]
class extends Component
{
    public Survey $survey;
    public string $url = '';

    public function mount(Survey $survey): void
    {
        $this->survey = $survey;
        $this->url = $survey->publicUrl();
    }
};
?>

@php $statusColor = ['draft' => 'zinc', 'published' => 'green', 'closed' => 'red']; @endphp

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('surveys.index')" wire:navigate />
        <div>
            <flux:heading size="xl">Share Survey</flux:heading>
            <flux:text class="mt-1">{{ $survey->title }}</flux:text>
        </div>
    </div>

    @if ($survey->status !== 'published' || ! $survey->is_public)
        <flux:callout variant="warning" icon="exclamation-triangle">
            This survey is <strong>{{ $survey->status }}</strong>{{ $survey->is_public ? '' : ' and public responses are disabled' }}.
            Set it to <strong>Published</strong> with public responses allowed for the link and QR to work.
        </flux:callout>
    @endif

    <flux:card class="flex flex-col items-center gap-4 text-center">
        <flux:heading size="lg">Scan to answer</flux:heading>
        <div id="qrcode" class="rounded-lg bg-white p-3"></div>
        <div class="w-full">
            <flux:input readonly value="{{ $url }}" />
            <div class="mt-2 flex justify-center gap-2">
                <flux:button size="sm" icon="clipboard" onclick="navigator.clipboard.writeText('{{ $url }}'); this.querySelector('span')?.replaceChildren(document.createTextNode('Copied!'))"><span>Copy link</span></flux:button>
                <flux:button size="sm" icon="printer" onclick="window.print()">Print</flux:button>
                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" href="{{ $url }}" target="_blank">Open</flux:button>
            </div>
        </div>
        <flux:text size="sm" class="text-zinc-500">Print this QR and post it at the RHU or hand it to residents. Anyone who scans it can answer the survey.</flux:text>
    </flux:card>

    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @endassets

    @script
    <script>
        const holder = document.getElementById('qrcode');
        if (holder) {
            holder.innerHTML = '';
            new QRCode(holder, { text: @js($url), width: 200, height: 200, correctLevel: QRCode.CorrectLevel.M });
        }
    </script>
    @endscript
</div>

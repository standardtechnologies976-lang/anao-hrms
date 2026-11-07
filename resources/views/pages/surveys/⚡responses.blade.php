<?php

use App\Models\Survey;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Survey Responses')]
class extends Component
{
    use WithPagination;

    public Survey $survey;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey->loadCount('responses');
    }

    #[Computed]
    public function responses()
    {
        return $this->survey->responses()->with(['citizen', 'submitter'])->orderByDesc('submitted_at')->paginate(20);
    }

    public function exportCsv()
    {
        $survey = $this->survey->load('questions');
        $questions = $survey->questions;

        return response()->streamDownload(function () use ($survey, $questions) {
            $out = fopen('php://output', 'w');
            $header = ['Respondent', 'Via', 'Submitted'];
            foreach ($questions as $q) { $header[] = $q->label; }
            fputcsv($out, $header);

            foreach ($survey->responses()->with('answers')->orderBy('submitted_at')->get() as $r) {
                $byQ = $r->answers->keyBy('survey_question_id');
                $row = [$r->displayName(), $r->submitted_via, $r->submitted_at?->format('Y-m-d H:i')];
                foreach ($questions as $q) {
                    $ans = $byQ->get($q->id);
                    $row[] = $ans ? $ans->formatted() : '';
                }
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'survey_'.$survey->slug.'_responses.csv', ['Content-Type' => 'text/csv']);
    }
};
?>

@php $viaColor = ['public' => 'blue', 'bhw' => 'emerald', 'staff' => 'zinc']; @endphp

<div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <flux:button icon="arrow-left" variant="ghost" :href="route('surveys.index')" wire:navigate />
            <div>
                <flux:heading size="xl">Responses</flux:heading>
                <flux:text class="mt-1">{{ $survey->title }} · {{ $survey->responses_count }} total</flux:text>
            </div>
        </div>
        <flux:button icon="arrow-down-tray" wire:click="exportCsv">Export CSV</flux:button>
    </div>

    <flux:table :paginate="$this->responses">
        <flux:table.columns>
            <flux:table.column>Respondent</flux:table.column>
            <flux:table.column>Via</flux:table.column>
            <flux:table.column>By</flux:table.column>
            <flux:table.column>Submitted</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->responses as $r)
                <flux:table.row :key="$r->id">
                    <flux:table.cell variant="strong">{{ $r->displayName() }}</flux:table.cell>
                    <flux:table.cell><flux:badge :color="$viaColor[$r->submitted_via]" size="sm">{{ ucfirst($r->submitted_via) }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $r->submitter?->name ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $r->submitted_at?->format('M j, Y g:i A') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="eye" :href="route('surveys.response.show', $r)" wire:navigate>View</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5" class="text-center">No responses yet.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

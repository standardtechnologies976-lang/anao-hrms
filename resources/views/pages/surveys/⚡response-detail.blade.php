<?php

use App\Models\SurveyResponse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('layouts::app')]
#[Title('Response')]
class extends Component
{
    public SurveyResponse $response;

    public function mount(SurveyResponse $response): void
    {
        $this->response = $response->load(['survey.questions', 'answers', 'citizen']);
    }
};
?>

<div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('surveys.responses', $response->survey)" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $response->displayName() }}</flux:heading>
            <flux:text class="mt-1">{{ $response->survey->title }} · {{ $response->submitted_at?->format('M j, Y g:i A') }}</flux:text>
        </div>
    </div>

    @php $byQ = $response->answers->keyBy('survey_question_id'); @endphp
    <div class="flex flex-col gap-3">
        @foreach ($response->survey->questions as $q)
            <flux:card>
                <flux:text class="font-medium">{{ $q->label }}</flux:text>
                <flux:text class="mt-1">{{ optional($byQ->get($q->id))->formatted() ?: '—' }}</flux:text>
            </flux:card>
        @endforeach
    </div>
</div>

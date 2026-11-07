<?php

use App\Models\Survey;
use App\Models\SurveyResponse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('layouts::public')]
#[Title('Survey')]
class extends Component
{
    public Survey $survey;
    public string $respondent_name = '';
    public array $answers = [];
    public bool $submitted = false;

    public function mount(string $slug): void
    {
        $this->survey = Survey::with('questions')->where('slug', $slug)->firstOrFail();
    }

    public function submit()
    {
        abort_unless($this->survey->isOpen() && $this->survey->is_public, 403);

        $rules = [];
        foreach ($this->survey->questions as $q) {
            if ($q->required) {
                $rules["answers.{$q->id}"] = 'required';
            }
        }
        $this->validate($rules, [], collect($this->survey->questions)->mapWithKeys(fn ($q) => ["answers.{$q->id}" => $q->label])->toArray());

        $response = SurveyResponse::create([
            'survey_id' => $this->survey->id,
            'respondent_name' => $this->respondent_name ?: null,
            'submitted_via' => 'public',
            'submitted_at' => now(),
        ]);

        foreach ($this->survey->questions as $q) {
            $val = $this->answers[$q->id] ?? null;
            if ($val === null || $val === '' || $val === []) {
                continue;
            }
            $response->answers()->create([
                'survey_question_id' => $q->id,
                'answer' => is_array($val) ? json_encode(array_values($val)) : $val,
            ]);
        }

        $this->submitted = true;
    }
};
?>

<div>
    @if ($submitted)
        <flux:card class="space-y-3 text-center">
            <flux:icon.check-circle class="mx-auto size-12 text-emerald-500" />
            <flux:heading size="lg">Salamat po!</flux:heading>
            <flux:text>Your response has been recorded.</flux:text>
        </flux:card>
    @elseif (! $survey->isOpen() || ! $survey->is_public)
        <flux:card class="text-center">
            <flux:heading size="lg">Survey unavailable</flux:heading>
            <flux:text class="mt-1">This survey is not currently accepting responses.</flux:text>
        </flux:card>
    @else
        <form wire:submit="submit" class="flex flex-col gap-4">
            <div>
                <flux:heading size="xl">{{ $survey->title }}</flux:heading>
                @if ($survey->description) <flux:text class="mt-1">{{ $survey->description }}</flux:text> @endif
            </div>

            <flux:card>
                <flux:input wire:model="respondent_name" label="Your name (optional)" />
            </flux:card>

            @foreach ($survey->questions as $q)
                <flux:card class="space-y-2" wire:key="pq-{{ $q->id }}">
                    <flux:text class="font-medium">{{ $q->label }} @if ($q->required)<span class="text-red-500">*</span>@endif</flux:text>

                    @switch($q->type)
                        @case('paragraph')
                            <flux:textarea wire:model="answers.{{ $q->id }}" rows="3" />
                            @break
                        @case('number')
                            <flux:input type="number" wire:model="answers.{{ $q->id }}" />
                            @break
                        @case('date')
                            <flux:input type="date" wire:model="answers.{{ $q->id }}" />
                            @break
                        @case('yesno')
                            <flux:radio.group wire:model="answers.{{ $q->id }}">
                                <flux:radio value="Yes" label="Yes" />
                                <flux:radio value="No" label="No" />
                            </flux:radio.group>
                            @break
                        @case('radio')
                            <flux:radio.group wire:model="answers.{{ $q->id }}">
                                @foreach ($q->options ?? [] as $opt)
                                    <flux:radio value="{{ $opt }}" label="{{ $opt }}" />
                                @endforeach
                            </flux:radio.group>
                            @break
                        @case('checkbox')
                            <flux:checkbox.group wire:model="answers.{{ $q->id }}">
                                @foreach ($q->options ?? [] as $opt)
                                    <flux:checkbox value="{{ $opt }}" label="{{ $opt }}" />
                                @endforeach
                            </flux:checkbox.group>
                            @break
                        @case('rating')
                            <flux:radio.group wire:model="answers.{{ $q->id }}" variant="segmented">
                                @foreach (['1','2','3','4','5'] as $n)
                                    <flux:radio value="{{ $n }}" label="{{ $n }}" />
                                @endforeach
                            </flux:radio.group>
                            @break
                        @default
                            <flux:input wire:model="answers.{{ $q->id }}" />
                    @endswitch
                    @error("answers.{$q->id}") <flux:text color="red" size="sm">{{ $message }}</flux:text> @enderror
                </flux:card>
            @endforeach

            <flux:button type="submit" variant="primary" class="w-full">Submit</flux:button>
        </form>
    @endif
</div>

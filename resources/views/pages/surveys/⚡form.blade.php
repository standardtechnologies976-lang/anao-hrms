<?php

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Survey Builder')]
class extends Component
{
    public ?int $editingId = null;

    public string $title = '';
    public string $description = '';
    public string $status = 'draft';
    public bool $is_public = true;

    public array $questions = [];

    public array $types = [
        'text' => 'Short text',
        'paragraph' => 'Paragraph',
        'number' => 'Number',
        'date' => 'Date',
        'yesno' => 'Yes / No',
        'radio' => 'Single choice',
        'checkbox' => 'Multiple choice',
        'rating' => 'Rating (1-5)',
    ];

    public function mount(?Survey $survey = null): void
    {
        if ($survey && $survey->exists) {
            $survey->loadMissing('questions');
            $this->editingId = $survey->id;
            $this->title = $survey->title;
            $this->description = $survey->description ?? '';
            $this->status = $survey->status;
            $this->is_public = (bool) $survey->is_public;
            $this->questions = $survey->questions->map(fn ($q) => [
                'id' => $q->id,
                'label' => $q->label,
                'type' => $q->type,
                'optionsText' => implode("\n", $q->options ?? []),
                'required' => (bool) $q->required,
            ])->toArray();
        }
        if (empty($this->questions)) {
            $this->addQuestion();
        }
    }

    public function addQuestion(): void
    {
        $this->questions[] = ['id' => null, 'label' => '', 'type' => 'text', 'optionsText' => '', 'required' => false];
    }

    public function removeQuestion(int $i): void
    {
        unset($this->questions[$i]);
        $this->questions = array_values($this->questions);
    }

    public function moveUp(int $i): void
    {
        if ($i <= 0) return;
        [$this->questions[$i - 1], $this->questions[$i]] = [$this->questions[$i], $this->questions[$i - 1]];
    }

    public function moveDown(int $i): void
    {
        if ($i >= count($this->questions) - 1) return;
        [$this->questions[$i + 1], $this->questions[$i]] = [$this->questions[$i], $this->questions[$i + 1]];
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,published,closed',
            'questions' => 'required|array|min:1',
            'questions.*.label' => 'required|string|max:500',
            'questions.*.type' => 'required|string',
        ], [], ['questions.*.label' => 'question']);

        $survey = Survey::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => $this->title,
                'description' => $this->description ?: null,
                'status' => $this->status,
                'is_public' => $this->is_public,
                'created_by' => $this->editingId ? Survey::find($this->editingId)?->created_by : auth()->id(),
            ]
        );

        // Sync questions (preserve ids so existing answers survive)
        $keepIds = [];
        foreach ($this->questions as $order => $q) {
            $options = in_array($q['type'], ['radio', 'checkbox'])
                ? array_values(array_filter(array_map('trim', explode("\n", $q['optionsText'] ?? ''))))
                : null;

            $payload = [
                'sort_order' => $order,
                'label' => $q['label'],
                'type' => $q['type'],
                'options' => $options,
                'required' => (bool) ($q['required'] ?? false),
            ];

            if (! empty($q['id'])) {
                SurveyQuestion::where('id', $q['id'])->update($payload);
                $keepIds[] = $q['id'];
            } else {
                $new = $survey->questions()->create($payload);
                $keepIds[] = $new->id;
            }
        }
        $survey->questions()->whereNotIn('id', $keepIds)->delete();

        Flux::toast(text: $this->editingId ? 'Survey saved.' : 'Survey created.', variant: 'success');
        return $this->redirect(route('surveys.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('surveys.index')" wire:navigate />
        <flux:heading size="xl">{{ $editingId ? 'Edit Survey' : 'New Survey' }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-4">
            <flux:input wire:model="title" label="Survey title" required />
            <flux:textarea wire:model="description" label="Description / instructions" rows="2" />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="status" label="Status">
                    <flux:select.option value="draft">Draft</flux:select.option>
                    <flux:select.option value="published">Published (accepting responses)</flux:select.option>
                    <flux:select.option value="closed">Closed</flux:select.option>
                </flux:select>
                <div class="flex items-end">
                    <flux:checkbox wire:model="is_public" label="Allow public link / QR responses" />
                </div>
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Questions</flux:heading>
                <flux:button size="sm" variant="filled" icon="plus" wire:click="addQuestion" type="button">Add question</flux:button>
            </div>
            @error('questions') <flux:text color="red">{{ $message }}</flux:text> @enderror

            @foreach ($questions as $i => $q)
                <div wire:key="q-{{ $i }}" class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex items-start gap-2">
                        <div class="flex-1 space-y-3">
                            <flux:input wire:model="questions.{{ $i }}.label" placeholder="Question {{ $i + 1 }}" />
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <flux:select wire:model.live="questions.{{ $i }}.type">
                                    @foreach ($types as $val => $label)
                                        <flux:select.option value="{{ $val }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <div class="flex items-center">
                                    <flux:checkbox wire:model="questions.{{ $i }}.required" label="Required" />
                                </div>
                            </div>
                            @if (in_array($q['type'], ['radio', 'checkbox']))
                                <flux:textarea wire:model="questions.{{ $i }}.optionsText" label="Options (one per line)" rows="3" placeholder="Option 1&#10;Option 2" />
                            @endif
                        </div>
                        <div class="flex flex-col gap-1">
                            <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveUp({{ $i }})" type="button" />
                            <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveDown({{ $i }})" type="button" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeQuestion({{ $i }})" type="button" />
                        </div>
                    </div>
                </div>
            @endforeach
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('surveys.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Save Survey' : 'Create Survey' }}</flux:button>
        </div>
    </form>
</div>

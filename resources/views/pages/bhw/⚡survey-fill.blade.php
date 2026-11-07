<?php

use App\Models\Citizen;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::bhw')]
#[Title('Fill Survey')]
class extends Component
{
    public Survey $survey;
    public $citizen_id = '';
    public string $citizenLabel = '';
    public string $citizenSearch = '';
    public array $answers = [];

    public function mount(Survey $survey): void
    {
        $this->survey = $survey->load('questions');
        if ($cid = request()->integer('citizen')) {
            $c = Citizen::find($cid);
            if ($c) {
                $this->citizen_id = $c->id;
                $this->citizenLabel = $c->full_name.' ('.$c->citizen_no.')';
            }
        }
    }

    #[Computed]
    public function citizenResults()
    {
        if ($this->citizen_id || mb_strlen($this->citizenSearch) < 2) {
            return collect();
        }
        $bx = auth()->user()->barangay_id;
        return Citizen::query()->when($bx, fn ($q) => $q->where('barangay_id', $bx))->search($this->citizenSearch)->orderBy('last_name')->limit(8)->get();
    }

    public function selectCitizen(int $id): void
    {
        $c = Citizen::findOrFail($id);
        $this->citizen_id = $c->id;
        $this->citizenLabel = $c->full_name.' ('.$c->citizen_no.')';
        $this->citizenSearch = '';
    }

    public function clearCitizen(): void
    {
        $this->reset(['citizen_id', 'citizenLabel', 'citizenSearch']);
    }

    public function submit()
    {
        $rules = [];
        foreach ($this->survey->questions as $q) {
            if ($q->required) {
                $rules["answers.{$q->id}"] = 'required';
            }
        }
        $this->validate($rules, [], collect($this->survey->questions)->mapWithKeys(fn ($q) => ["answers.{$q->id}" => $q->label])->toArray());

        $citizen = $this->citizen_id ? Citizen::find($this->citizen_id) : null;
        $response = SurveyResponse::create([
            'survey_id' => $this->survey->id,
            'citizen_id' => $this->citizen_id ?: null,
            'barangay_id' => $citizen?->barangay_id ?? auth()->user()->barangay_id,
            'submitted_via' => 'bhw',
            'submitted_by' => auth()->id(),
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

        Flux::toast(text: 'Survey submitted.', variant: 'success');
        return $this->redirect(route('bhw-portal.surveys'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-4">
    <div class="flex items-center gap-2">
        <flux:button icon="arrow-left" variant="ghost" :href="route('bhw-portal.surveys')" wire:navigate />
        <flux:heading size="lg">{{ $survey->title }}</flux:heading>
    </div>

    <form wire:submit="submit" class="flex flex-col gap-4">
        <flux:card class="space-y-2">
            <flux:text class="font-medium">Resident (optional)</flux:text>
            @if ($citizen_id)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <span class="font-medium">{{ $citizenLabel }}</span>
                    <flux:button size="sm" variant="ghost" wire:click="clearCitizen" type="button">Change</flux:button>
                </div>
            @else
                <flux:input wire:model.live.debounce.300ms="citizenSearch" placeholder="Search resident..." icon="magnifying-glass" />
                @if ($this->citizenResults->isNotEmpty())
                    <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @foreach ($this->citizenResults as $c)
                            <button type="button" wire:click="selectCitizen({{ $c->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ $c->full_name }} <span class="text-zinc-500">- {{ $c->citizen_no }}</span></button>
                        @endforeach
                    </div>
                @endif
            @endif
        </flux:card>

        @foreach ($survey->questions as $q)
            <flux:card class="space-y-2" wire:key="bq-{{ $q->id }}">
                <flux:text class="font-medium">{{ $q->label }} @if ($q->required)<span class="text-red-500">*</span>@endif</flux:text>
                @switch($q->type)
                    @case('paragraph') <flux:textarea wire:model="answers.{{ $q->id }}" rows="3" /> @break
                    @case('number') <flux:input type="number" wire:model="answers.{{ $q->id }}" /> @break
                    @case('date') <flux:input type="date" wire:model="answers.{{ $q->id }}" /> @break
                    @case('yesno')
                        <flux:radio.group wire:model="answers.{{ $q->id }}">
                            <flux:radio value="Yes" label="Yes" /><flux:radio value="No" label="No" />
                        </flux:radio.group> @break
                    @case('radio')
                        <flux:radio.group wire:model="answers.{{ $q->id }}">
                            @foreach ($q->options ?? [] as $opt) <flux:radio value="{{ $opt }}" label="{{ $opt }}" /> @endforeach
                        </flux:radio.group> @break
                    @case('checkbox')
                        <flux:checkbox.group wire:model="answers.{{ $q->id }}">
                            @foreach ($q->options ?? [] as $opt) <flux:checkbox value="{{ $opt }}" label="{{ $opt }}" /> @endforeach
                        </flux:checkbox.group> @break
                    @case('rating')
                        <flux:radio.group wire:model="answers.{{ $q->id }}" variant="segmented">
                            @foreach (['1','2','3','4','5'] as $n) <flux:radio value="{{ $n }}" label="{{ $n }}" /> @endforeach
                        </flux:radio.group> @break
                    @default <flux:input wire:model="answers.{{ $q->id }}" />
                @endswitch
                @error("answers.{$q->id}") <flux:text color="red" size="sm">{{ $message }}</flux:text> @enderror
            </flux:card>
        @endforeach

        <flux:button type="submit" variant="primary" class="w-full">Submit Survey</flux:button>
    </form>
</div>

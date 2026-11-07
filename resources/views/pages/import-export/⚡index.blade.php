<?php

use App\Models\AssistanceRequest;
use App\Models\Barangay;
use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\Household;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Data Import / Export')]
class extends Component
{
    use WithFileUploads;

    public $csv;
    public ?array $importResult = null;

    public string $morbidityFrom = '';
    public string $morbidityTo = '';

    public function mount(): void
    {
        $this->morbidityFrom = now()->startOfMonth()->toDateString();
        $this->morbidityTo = now()->toDateString();
    }

    private function streamCsv(string $filename, array $header, $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportCitizens()
    {
        $rows = Citizen::with('barangay')->orderBy('last_name')->get()->map(fn ($c) => [
            $c->citizen_no, $c->last_name, $c->first_name, $c->middle_name, $c->suffix,
            $c->sex, $c->birth_date?->format('Y-m-d'), $c->barangay?->name, $c->purok,
            $c->contact_no, $c->philhealth_no,
            $c->is_senior ? 'Y' : '', $c->is_pwd ? 'Y' : '', $c->is_4ps ? 'Y' : '',
            $c->is_solo_parent ? 'Y' : '', $c->is_pregnant ? 'Y' : '',
        ]);
        return $this->streamCsv('citizens_'.now()->format('Ymd').'.csv',
            ['Citizen No', 'Last Name', 'First Name', 'Middle Name', 'Suffix', 'Sex', 'Birth Date', 'Barangay', 'Purok', 'Contact', 'PhilHealth', 'Senior', 'PWD', '4Ps', 'Solo Parent', 'Pregnant'],
            $rows);
    }

    public function exportHouseholds()
    {
        $rows = Household::with(['barangay', 'head'])->withCount('members')->get()->map(fn ($h) => [
            $h->household_no, $h->barangay?->name, $h->purok, $h->address,
            $h->head?->full_name, $h->members_count, $h->house_type, $h->water_source,
            $h->toilet_facility, $h->has_electricity ? 'Y' : 'N', $h->surveyed_at?->format('Y-m-d'),
        ]);
        return $this->streamCsv('households_'.now()->format('Ymd').'.csv',
            ['Household No', 'Barangay', 'Purok', 'Address', 'Head', 'Members', 'House Type', 'Water Source', 'Toilet', 'Electricity', 'Surveyed'],
            $rows);
    }

    public function exportConsultations()
    {
        $rows = Consultation::with(['citizen', 'physician'])->orderByDesc('consulted_at')->get()->map(fn ($c) => [
            $c->consulted_at?->format('Y-m-d'), $c->citizen?->citizen_no, $c->citizen?->full_name,
            $c->diagnosis, $c->icd10_code, $c->disposition, $c->physician?->name,
        ]);
        return $this->streamCsv('consultations_'.now()->format('Ymd').'.csv',
            ['Date', 'Citizen No', 'Name', 'Diagnosis', 'ICD-10', 'Disposition', 'Physician'],
            $rows);
    }

    public function exportAssistance()
    {
        $rows = AssistanceRequest::with(['citizen', 'type'])->orderByDesc('request_date')->get()->map(fn ($r) => [
            $r->reference_no, $r->request_date?->format('Y-m-d'), $r->citizen?->full_name,
            $r->type?->name, $r->amount_requested, $r->amount_granted, $r->status,
        ]);
        return $this->streamCsv('assistance_'.now()->format('Ymd').'.csv',
            ['Ref No', 'Date', 'Citizen', 'Type', 'Requested', 'Granted', 'Status'],
            $rows);
    }

    public function exportMorbidity()
    {
        $rows = Consultation::whereNotNull('diagnosis')->where('diagnosis', '!=', '')
            ->whereBetween('consulted_at', [Carbon::parse($this->morbidityFrom)->startOfDay(), Carbon::parse($this->morbidityTo)->endOfDay()])
            ->selectRaw('diagnosis, count(*) as cases')
            ->groupBy('diagnosis')->orderByDesc('cases')->get()
            ->map(fn ($r) => [$r->diagnosis, $r->cases]);
        return $this->streamCsv('morbidity_'.$this->morbidityFrom.'_'.$this->morbidityTo.'.csv',
            ['Diagnosis', 'Cases'], $rows);
    }

    public function citizenTemplate()
    {
        return $this->streamCsv('citizen_import_template.csv',
            ['last_name', 'first_name', 'middle_name', 'suffix', 'sex', 'birth_date', 'barangay', 'purok', 'contact_no', 'philhealth_no'],
            [['Dela Cruz', 'Juan', 'Santos', '', 'male', '1990-01-15', 'Poblacion', 'Purok 1', '09171234567', '']]);
    }

    public function import()
    {
        $this->validate(['csv' => 'required|file|mimes:csv,txt|max:5120']);

        $path = $this->csv->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $barangays = Barangay::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id]);

        $imported = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, array_pad($row, count($header), null));
            if (empty($data['last_name']) || empty($data['first_name'])) {
                $skipped++;
                continue;
            }
            Citizen::create([
                'last_name'     => trim($data['last_name']),
                'first_name'    => trim($data['first_name']),
                'middle_name'   => $data['middle_name'] ?: null,
                'suffix'        => $data['suffix'] ?: null,
                'sex'           => in_array(strtolower($data['sex'] ?? ''), ['male', 'female']) ? strtolower($data['sex']) : null,
                'birth_date'    => ! empty($data['birth_date']) ? $data['birth_date'] : null,
                'barangay_id'   => $barangays[strtolower(trim($data['barangay'] ?? ''))] ?? null,
                'purok'         => $data['purok'] ?: null,
                'contact_no'    => $data['contact_no'] ?: null,
                'philhealth_no' => $data['philhealth_no'] ?: null,
            ]);
            $imported++;
        }
        fclose($handle);

        $this->importResult = ['imported' => $imported, 'skipped' => $skipped];
        $this->reset('csv');
        Flux::toast(text: "Imported {$imported} citizens ({$skipped} skipped).", variant: 'success');
    }
};
?>

<div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-2">
    <div>
        <flux:heading size="xl">Data Import / Export</flux:heading>
        <flux:text class="mt-1">Bulk data tools and DOH / WAH reporting exports</flux:text>
    </div>

    <flux:card class="space-y-3">
        <flux:heading size="lg">Export Data (CSV)</flux:heading>
        <flux:text>Download current records as spreadsheets.</flux:text>
        <div class="flex flex-wrap gap-2">
            <flux:button icon="arrow-down-tray" wire:click="exportCitizens">Citizens</flux:button>
            <flux:button icon="arrow-down-tray" wire:click="exportHouseholds">Households</flux:button>
            <flux:button icon="arrow-down-tray" wire:click="exportConsultations">Consultations</flux:button>
            <flux:button icon="arrow-down-tray" wire:click="exportAssistance">MSWD Assistance</flux:button>
        </div>
    </flux:card>

    <flux:card class="space-y-3">
        <flux:heading size="lg">DOH / WAH Reporting</flux:heading>
        <flux:text>Morbidity report (diagnoses tally) for a date range, for FHSIS / WAH encoding.</flux:text>
        <div class="flex flex-wrap items-center gap-3">
            <flux:input type="date" wire:model="morbidityFrom" class="max-w-[10rem]" />
            <flux:text size="sm">to</flux:text>
            <flux:input type="date" wire:model="morbidityTo" class="max-w-[10rem]" />
            <flux:button variant="primary" icon="document-chart-bar" wire:click="exportMorbidity">Export Morbidity</flux:button>
        </div>
        <flux:text size="sm" class="text-zinc-500">Note: WAH/iClinicSys have no open import API. This CSV is for manual encoding or a future adapter, keeping this system a complement to WAH rather than a replacement.</flux:text>
    </flux:card>

    <flux:card class="space-y-3">
        <flux:heading size="lg">Import Citizens</flux:heading>
        <flux:text>Bulk-load residents from a CSV. Download the template first to see the columns.</flux:text>
        <flux:button size="sm" variant="ghost" icon="arrow-down-tray" wire:click="citizenTemplate">Download template</flux:button>

        <form wire:submit="import" class="space-y-3">
            <flux:input type="file" wire:model="csv" label="CSV file" accept=".csv" />
            @error('csv') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="import">Import</span>
                <span wire:loading wire:target="import">Importing...</span>
            </flux:button>
        </form>

        @if ($importResult)
            <flux:callout variant="success" icon="check-circle">
                Imported {{ $importResult['imported'] }} citizens. Skipped {{ $importResult['skipped'] }} (missing name).
            </flux:callout>
        @endif
    </flux:card>
</div>

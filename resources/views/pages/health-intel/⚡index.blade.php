<?php

use App\Models\Barangay;
use App\Models\Citizen;
use App\Models\Consultation;
use App\Support\ForecastService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Health Intelligence')]
class extends Component
{
    public string $barangayFilter = '';

    private array $chronic = [
        'Hypertension' => 'Hypertension',
        'Diabetes' => 'Diabetes',
        'Tuberculosis' => 'Tuberculosis',
        'Asthma' => 'Asthma',
    ];

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    #[Computed]
    public function scopeLabel(): string
    {
        return $this->barangayFilter ? (Barangay::find($this->barangayFilter)?->name ?? 'Barangay') : 'Whole Municipality of Anao';
    }

    private function citizens()
    {
        return Citizen::query()->when($this->barangayFilter, fn ($q) => $q->where('barangay_id', $this->barangayFilter));
    }

    private function consultations()
    {
        return Consultation::query()->when($this->barangayFilter, fn ($q) => $q->whereHas('citizen', fn ($c) => $c->where('barangay_id', $this->barangayFilter)));
    }

    private function chronicCount(string $needle): int
    {
        return $this->citizens()->whereHas('consultations', fn ($q) => $q->where('diagnosis', 'like', "%{$needle}%"))->count();
    }

    #[Computed]
    public function risk(): array
    {
        $citizens = $this->citizens()->get(['id', 'first_name', 'last_name', 'middle_name', 'suffix', 'birth_date', 'is_senior', 'is_pregnant', 'is_pwd']);
        $ids = $citizens->pluck('id');

        $dx = Consultation::whereIn('citizen_id', $ids)
            ->where(function ($q) {
                foreach ($this->chronic as $needle) {
                    $q->orWhere('diagnosis', 'like', "%{$needle}%");
                }
            })
            ->get(['citizen_id', 'diagnosis'])
            ->groupBy('citizen_id');

        $scored = [];
        foreach ($citizens as $c) {
            $factors = [];
            $score = 0;
            if ($c->is_senior) { $score += 2; $factors[] = 'Senior'; }
            if ($c->is_pregnant) { $score += 2; $factors[] = 'Pregnant'; }
            if ($c->is_pwd) { $score += 1; $factors[] = 'PWD'; }
            if (($c->birth_date?->age ?? 0) >= 70) { $score += 1; }
            $conds = $dx->get($c->id, collect())->pluck('diagnosis')->implode(' ');
            foreach (['Hypertension' => 2, 'Diabetes' => 2, 'Tuberculosis' => 3, 'Asthma' => 1] as $cond => $w) {
                if (stripos($conds, $cond) !== false) { $score += $w; $factors[] = $cond; }
            }
            if ($score >= 4) {
                $scored[] = ['name' => $c->full_name, 'age' => $c->birth_date?->age, 'score' => $score, 'factors' => array_unique($factors)];
            }
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        return ['count' => count($scored), 'list' => array_slice($scored, 0, 20)];
    }

    #[Computed]
    public function snapshot(): array
    {
        return [
            'residents' => $this->citizens()->count(),
            'seniors' => $this->citizens()->where('is_senior', true)->count(),
            'pregnant' => $this->citizens()->where('is_pregnant', true)->count(),
            'pwd' => $this->citizens()->where('is_pwd', true)->count(),
            'hypertensive' => $this->chronicCount('Hypertension'),
            'diabetic' => $this->chronicCount('Diabetes'),
            'tb' => $this->chronicCount('Tuberculosis'),
            'high_risk' => $this->risk()['count'],
        ];
    }

    private function incidenceSeries(): array
    {
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labels[] = $m->format('M');
            $data[] = $this->consultations()->whereYear('consulted_at', $m->year)->whereMonth('consulted_at', $m->month)->count();
        }
        return ['labels' => $labels, 'data' => $data];
    }

    #[Computed]
    public function forecast(): array
    {
        $hist = $this->incidenceSeries();
        $projected = ForecastService::linear($hist['data'], 3);
        $futureLabels = [];
        for ($k = 1; $k <= 3; $k++) {
            $futureLabels[] = now()->addMonths($k)->format('M');
        }
        return [
            'labels' => array_merge($hist['labels'], $futureLabels),
            'actual' => array_merge($hist['data'], [null, null, null]),
            'forecast' => array_merge(array_fill(0, 5, null), [end($hist['data'])], $projected),
            'next' => $projected[0] ?? 0,
            'trend' => ForecastService::trendLabel($hist['data']),
        ];
    }

    #[Computed]
    public function topDiseases(): array
    {
        $rows = $this->consultations()->whereNotNull('diagnosis')->where('diagnosis', '!=', '')
            ->selectRaw('diagnosis, count(*) as c')->groupBy('diagnosis')->orderByDesc('c')->limit(7)->get();
        return ['labels' => $rows->pluck('diagnosis')->all(), 'data' => $rows->pluck('c')->all()];
    }

    #[Computed]
    public function barangayMatrix()
    {
        return Barangay::query()
            ->when($this->barangayFilter, fn ($q) => $q->where('id', $this->barangayFilter))
            ->withCount('citizens')
            ->orderBy('name')->get()
            ->map(function ($b) {
                $consults = Consultation::whereHas('citizen', fn ($c) => $c->where('barangay_id', $b->id))
                    ->where('consulted_at', '>=', now()->subDays(90))->count();
                $top = Consultation::whereHas('citizen', fn ($c) => $c->where('barangay_id', $b->id))
                    ->whereNotNull('diagnosis')->where('diagnosis', '!=', '')
                    ->selectRaw('diagnosis, count(*) as c')->groupBy('diagnosis')->orderByDesc('c')->value('diagnosis');
                $pregnant = Citizen::where('barangay_id', $b->id)->where('is_pregnant', true)->count();
                return (object) [
                    'name' => $b->name,
                    'residents' => $b->citizens_count,
                    'consults' => $consults,
                    'top' => $top ?? '-',
                    'pregnant' => $pregnant,
                ];
            });
    }

    #[Computed]
    public function chartsPayload(): array
    {
        return ['incidence' => $this->forecast(), 'diseases' => $this->topDiseases()];
    }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <flux:icon.cpu-chip class="size-6 text-indigo-500" />
                <flux:heading size="xl">Health Intelligence</flux:heading>
            </div>
            <flux:text class="mt-1">Population health monitoring and forecasting · {{ $this->scopeLabel }}</flux:text>
        </div>
        <flux:select wire:model.live="barangayFilter" placeholder="Whole municipality" class="max-w-xs">
            <flux:select.option value="">Whole Municipality of Anao</flux:select.option>
            @foreach ($this->barangayList as $b)
                <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:callout icon="sparkles" variant="secondary">
        <flux:callout.heading>Forecasts are a statistical baseline (not ML yet)</flux:callout.heading>
        <flux:callout.text>
            Projections use a linear trend on actual history, and risk flags use transparent rules. The forecast and risk engine sit behind a swappable service, ready to be upgraded to a machine-learning model (a separate Python service) once enough data is collected.
        </flux:callout.text>
    </flux:callout>

    {{-- Monitoring snapshot --}}
    <div>
        <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Population Health Snapshot</flux:text>
        @php
            $tiles = [
                ['Residents', $this->snapshot['residents'], 'text-zinc-900 dark:text-white'],
                ['Seniors', $this->snapshot['seniors'], 'text-amber-600'],
                ['Pregnant', $this->snapshot['pregnant'], 'text-pink-600'],
                ['PWD', $this->snapshot['pwd'], 'text-blue-600'],
                ['Hypertensive', $this->snapshot['hypertensive'], 'text-red-600'],
                ['Diabetic', $this->snapshot['diabetic'], 'text-red-600'],
                ['TB cases', $this->snapshot['tb'], 'text-orange-600'],
                ['High-risk', $this->snapshot['high_risk'], 'text-rose-600'],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
            @foreach ($tiles as [$label, $value, $color])
                <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="truncate text-xs text-zinc-500">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold {{ $color }}">{{ number_format($value) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Forecast + diseases --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <flux:card>
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Consultation Forecast</flux:heading>
                <flux:badge :color="$this->forecast['trend'] === 'rising' ? 'red' : ($this->forecast['trend'] === 'declining' ? 'green' : 'zinc')" size="sm">
                    {{ ucfirst($this->forecast['trend']) }}
                </flux:badge>
            </div>
            <flux:text size="sm" class="mb-2">Projected next month: <span class="font-bold text-indigo-600">{{ number_format($this->forecast['next']) }}</span> consultations</flux:text>
            <div wire:ignore class="h-56"><canvas id="hiIncidence"></canvas></div>
        </flux:card>
        <flux:card>
            <flux:heading size="lg" class="mb-3">Disease Burden ({{ $this->scopeLabel }})</flux:heading>
            <div wire:ignore class="h-56"><canvas id="hiDiseases"></canvas></div>
        </flux:card>
    </div>

    {{-- Per-barangay monitoring --}}
    <div>
        <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Per-Barangay Monitoring (last 90 days)</flux:text>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Barangay</flux:table.column>
                <flux:table.column>Residents</flux:table.column>
                <flux:table.column>Consultations</flux:table.column>
                <flux:table.column>Top Concern</flux:table.column>
                <flux:table.column>Pregnant</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->barangayMatrix as $row)
                    <flux:table.row :key="$row->name">
                        <flux:table.cell variant="strong">{{ $row->name }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($row->residents) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($row->consults) }}</flux:table.cell>
                        <flux:table.cell>{{ $row->top }}</flux:table.cell>
                        <flux:table.cell>{{ $row->pregnant }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- High-risk register --}}
    <div>
        <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">High-Risk Residents ({{ number_format($this->risk['count']) }} flagged)</flux:text>
        <flux:card>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->risk['list'] as $r)
                    <div class="flex items-center justify-between py-2">
                        <div>
                            <span class="font-medium">{{ $r['name'] }}</span>
                            @if ($r['age']) <flux:text size="sm" class="inline">· {{ $r['age'] }} yrs</flux:text> @endif
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach ($r['factors'] as $f)
                                    <flux:badge size="sm" color="zinc">{{ $f }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                        <flux:badge :color="$r['score'] >= 6 ? 'red' : 'amber'">Risk {{ $r['score'] }}</flux:badge>
                    </div>
                @empty
                    <flux:text class="py-2 text-zinc-500">No high-risk residents flagged in this scope.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>

    <script type="application/json" id="hi-data">@js($this->chartsPayload)</script>

    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        window.__hi = window.__hi || {};
        const hiDraw = () => {
            const el = document.getElementById('hi-data');
            if (!el || typeof Chart === 'undefined') return;
            const d = JSON.parse(el.textContent);
            const inc = document.getElementById('hiIncidence');
            const dis = document.getElementById('hiDiseases');
            if (window.__hi.inc) window.__hi.inc.destroy();
            if (window.__hi.dis) window.__hi.dis.destroy();
            if (inc) {
                window.__hi.inc = new Chart(inc, {
                    type: 'line',
                    data: { labels: d.incidence.labels, datasets: [
                        { label: 'Actual', data: d.incidence.actual, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.12)', fill: true, tension: 0.3 },
                        { label: 'Forecast', data: d.incidence.forecast, borderColor: '#6366f1', borderDash: [6,4], pointStyle: 'triangle', tension: 0.3 },
                    ]},
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
            if (dis) {
                window.__hi.dis = new Chart(dis, {
                    type: 'bar',
                    data: { labels: d.diseases.labels, datasets: [{ label: 'Cases', data: d.diseases.data, backgroundColor: '#8b5cf6' }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }
        };
        hiDraw();
        $wire.$watch('barangayFilter', () => setTimeout(hiDraw, 60));
    </script>
    @endscript
</div>

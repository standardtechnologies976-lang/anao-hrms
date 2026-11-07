<?php

use App\Models\Admission;
use App\Models\Appointment;
use App\Models\AssistanceRequest;
use App\Models\Barangay;
use App\Models\Bed;
use App\Models\Citizen;
use App\Models\Consultation;
use App\Models\FieldVisit;
use App\Models\Household;
use App\Models\ImagingExam;
use App\Models\LabOrder;
use App\Models\Medicine;
use App\Models\RadiologyOrder;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::app')]
#[Title('Executive Dashboards')]
class extends Component
{
    #[Computed]
    public function kpis(): array
    {
        $now = now();
        $beds = Bed::count();
        return [
            'residents' => Citizen::count(),
            'households' => Household::count(),
            'consultations_month' => Consultation::whereYear('consulted_at', $now->year)->whereMonth('consulted_at', $now->month)->count(),
            'admissions_active' => Admission::where('status', 'admitted')->count(),
            'bed_occupancy' => $beds > 0 ? round(Bed::where('is_occupied', true)->count() / $beds * 100) : 0,
            'appointments_pending' => Appointment::where('status', 'pending')->count(),
            'assistance_released_year' => (float) AssistanceRequest::where('status', 'released')->whereYear('released_at', $now->year)->sum('amount_granted'),
            'low_stock' => Medicine::where('active', true)->get()->filter->needsReorder()->count(),
            'bhw_visits_month' => FieldVisit::whereYear('visit_date', $now->year)->whereMonth('visit_date', $now->month)->count(),
            'lab_month' => LabOrder::whereYear('ordered_at', $now->year)->whereMonth('ordered_at', $now->month)->count(),
            'imaging_month' => RadiologyOrder::whereYear('ordered_at', $now->year)->whereMonth('ordered_at', $now->month)->count(),
        ];
    }

    #[Computed]
    public function charts(): array
    {
        $now = now();

        $pop = Barangay::withCount('citizens')->orderBy('name')->get();

        $months = [];
        $consult = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = $now->copy()->subMonths($i);
            $months[] = $m->format('M Y');
            $consult[] = Consultation::whereYear('consulted_at', $m->year)->whereMonth('consulted_at', $m->month)->count();
        }

        $dx = Consultation::whereNotNull('diagnosis')->where('diagnosis', '!=', '')
            ->selectRaw('diagnosis, count(*) as c')->groupBy('diagnosis')->orderByDesc('c')->limit(5)->get();

        $assist = AssistanceRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $bhw = FieldVisit::selectRaw('visit_type, count(*) as c')->groupBy('visit_type')->orderByDesc('c')->get();

        return [
            'barangay' => ['labels' => $pop->pluck('name'), 'data' => $pop->pluck('citizens_count')],
            'sector' => [
                'labels' => ['Senior', 'PWD', '4Ps', 'Solo Parent', 'Pregnant'],
                'data' => [
                    Citizen::where('is_senior', true)->count(),
                    Citizen::where('is_pwd', true)->count(),
                    Citizen::where('is_4ps', true)->count(),
                    Citizen::where('is_solo_parent', true)->count(),
                    Citizen::where('is_pregnant', true)->count(),
                ],
            ],
            'consult' => ['labels' => $months, 'data' => $consult],
            'dx' => ['labels' => $dx->pluck('diagnosis'), 'data' => $dx->pluck('c')],
            'assist' => ['labels' => $assist->keys()->map(fn ($s) => ucfirst($s)), 'data' => $assist->values()],
            'bhw' => ['labels' => $bhw->pluck('visit_type'), 'data' => $bhw->pluck('c')],
        ];
    }
};
?>

<div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-2">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <flux:heading size="xl">Executive Dashboard</flux:heading>
            <flux:text class="mt-1">Municipality of Anao, Tarlac · Office of the Mayor &amp; MHO</flux:text>
        </div>
        <flux:text size="sm" class="text-zinc-500">As of {{ now()->format('F j, Y') }}</flux:text>
    </div>

    {{-- Key indicators (compact) --}}
    <div>
        <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Key Indicators</flux:text>
        @php
            $tiles = [
                ['Residents', number_format($this->kpis['residents']), 'text-zinc-900 dark:text-white'],
                ['Households', number_format($this->kpis['households']), 'text-zinc-900 dark:text-white'],
                ['Consultations (mo)', number_format($this->kpis['consultations_month']), 'text-blue-600'],
                ['Active Admissions', number_format($this->kpis['admissions_active']), 'text-emerald-600'],
                ['Bed Occupancy', $this->kpis['bed_occupancy'].'%', 'text-amber-600'],
                ['Pending Appointments', number_format($this->kpis['appointments_pending']), 'text-blue-600'],
                ['Assistance (yr)', '₱'.number_format($this->kpis['assistance_released_year']), 'text-emerald-600'],
                ['Low-stock Meds', number_format($this->kpis['low_stock']), 'text-red-600'],
                ['BHW Visits (mo)', number_format($this->kpis['bhw_visits_month']), 'text-emerald-600'],
                ['Lab Orders (mo)', number_format($this->kpis['lab_month']), 'text-blue-600'],
                ['Imaging (mo)', number_format($this->kpis['imaging_month']), 'text-blue-600'],
            ];
        @endphp
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($tiles as [$label, $value, $color])
                <div class="rounded-xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="truncate text-xs text-zinc-500">{{ $label }}</div>
                    <div class="mt-1 text-xl font-bold {{ $color }}">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Analytics --}}
    <div>
        <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Analytics</flux:text>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <flux:card>
                <flux:heading size="lg" class="mb-3">Population by Barangay</flux:heading>
                <div class="h-64"><canvas id="chartBarangay"></canvas></div>
            </flux:card>
            <flux:card>
                <flux:heading size="lg" class="mb-3">Sectoral Distribution</flux:heading>
                <div class="h-64"><canvas id="chartSector"></canvas></div>
            </flux:card>
            <flux:card>
                <flux:heading size="lg" class="mb-3">Consultations (6 months)</flux:heading>
                <div class="h-64"><canvas id="chartConsult"></canvas></div>
            </flux:card>
            <flux:card>
                <flux:heading size="lg" class="mb-3">Top Diagnoses</flux:heading>
                <div class="h-64"><canvas id="chartDx"></canvas></div>
            </flux:card>
            <flux:card>
                <flux:heading size="lg" class="mb-3">Assistance Requests by Status</flux:heading>
                <div class="h-64"><canvas id="chartAssist"></canvas></div>
            </flux:card>
            <flux:card>
                <flux:heading size="lg" class="mb-3">BHW Field Visits by Type</flux:heading>
                <div class="h-64"><canvas id="chartBhw"></canvas></div>
            </flux:card>
        </div>
    </div>

    @assets
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    @endassets

    @script
    <script>
        const palette = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];
        const data = @js($this->charts);
        window.__anaoCharts = window.__anaoCharts || {};
        function draw(id, config) {
            const el = document.getElementById(id);
            if (!el) return;
            if (window.__anaoCharts[id]) { window.__anaoCharts[id].destroy(); }
            window.__anaoCharts[id] = new Chart(el, config);
        }
        draw('chartBarangay', { type: 'bar', data: { labels: data.barangay.labels, datasets: [{ label: 'Residents', data: data.barangay.data, backgroundColor: '#10b981' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        draw('chartSector', { type: 'doughnut', data: { labels: data.sector.labels, datasets: [{ data: data.sector.data, backgroundColor: palette }] }, options: { responsive: true, maintainAspectRatio: false } });
        draw('chartConsult', { type: 'line', data: { labels: data.consult.labels, datasets: [{ label: 'Consultations', data: data.consult.data, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.15)', fill: true, tension: 0.3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        draw('chartDx', { type: 'bar', data: { labels: data.dx.labels, datasets: [{ label: 'Cases', data: data.dx.data, backgroundColor: '#8b5cf6' }] }, options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
        draw('chartAssist', { type: 'doughnut', data: { labels: data.assist.labels, datasets: [{ data: data.assist.data, backgroundColor: palette }] }, options: { responsive: true, maintainAspectRatio: false } });
        draw('chartBhw', { type: 'bar', data: { labels: data.bhw.labels, datasets: [{ label: 'Visits', data: data.bhw.data, backgroundColor: '#14b8a6' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
    </script>
    @endscript
</div>

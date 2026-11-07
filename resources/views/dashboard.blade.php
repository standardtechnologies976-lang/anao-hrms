<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $stats = [
            ['Residents', \App\Models\Citizen::count(), 'identification'],
            ['Consultations today', \App\Models\Consultation::whereDate('consulted_at', today())->count(), 'clipboard-document-check'],
            ['Waiting in triage', \App\Models\TriageAssessment::whereDate('assessed_at', today())->where('status', 'waiting')->count(), 'clipboard-document-list'],
            ['Pending appointments', \App\Models\Appointment::where('status', 'pending')->count(), 'calendar-days'],
        ];
    @endphp

    <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-2">
        <div>
            <flux:heading size="xl">Welcome, {{ $user->name }}</flux:heading>
            <flux:text class="mt-1">Anao Health Information System · {{ now()->format('l, F j, Y') }}</flux:text>
        </div>

        {{-- Headline stats --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ($stats as [$label, $value, $icon])
                <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                        <flux:icon :icon="$icon" class="size-5 text-zinc-600 dark:text-zinc-300" />
                    </div>
                    <div>
                        <div class="text-xs text-zinc-500">{{ $label }}</div>
                        <div class="text-2xl font-bold">{{ number_format($value) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($user->canAccess('dashboards') || $user->isAdmin())
            <flux:button variant="primary" icon="chart-bar" :href="route('dashboards.index')" wire:navigate class="self-start">Open Executive Dashboard</flux:button>
        @endif

        {{-- Quick access to modules the user can open --}}
        <div>
            <flux:text class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">Quick Access</flux:text>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Support\Features::assignable() as $key => $meta)
                    @if (\Illuminate\Support\Facades\Route::has($meta['route']) && $user->canAccess($key))
                        <a href="{{ route($meta['route']) }}" wire:navigate
                           class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
                            <flux:icon :icon="$meta['icon']" class="size-5 text-zinc-500" />
                            <span class="font-medium">{{ $meta['label'] }}</span>
                        </a>
                    @endif
                @endforeach

                @if ($user->isAdmin())
                    <a href="{{ route('users.index') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
                        <flux:icon icon="user-group" class="size-5 text-zinc-500" />
                        <span class="font-medium">Users &amp; Access</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light;">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ filled($title ?? null) ? $title.' - '.config('app.name') : config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 pb-20 text-slate-900">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex max-w-md items-center justify-between px-4 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white">
                        <flux:icon.users class="size-5" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold leading-tight text-slate-800">BHW Portal</div>
                        <div class="text-xs text-emerald-700">{{ auth()->user()->barangay?->name ?? 'All barangays' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" size="sm" variant="ghost" icon="arrow-right-start-on-rectangle" />
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-md px-4 py-4">
            {{ $slot }}
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-10 border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-md grid-cols-4">
                <a href="{{ route('bhw-portal.index') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('bhw-portal.index') ? 'text-emerald-600' : 'text-slate-500' }}">
                    <flux:icon.home class="size-6" />
                    Home
                </a>
                <a href="{{ route('bhw-portal.residents') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('bhw-portal.residents') ? 'text-emerald-600' : 'text-slate-500' }}">
                    <flux:icon.users class="size-6" />
                    Residents
                </a>
                <a href="{{ route('bhw-portal.visits') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('bhw-portal.visits') ? 'text-emerald-600' : 'text-slate-500' }}">
                    <flux:icon.clipboard-document-list class="size-6" />
                    Visits
                </a>
                <a href="{{ route('bhw-portal.visit.create') }}" wire:navigate class="flex flex-col items-center gap-0.5 py-2 text-xs {{ request()->routeIs('bhw-portal.visit.*') ? 'text-emerald-600' : 'text-slate-500' }}">
                    <flux:icon.plus-circle class="size-6" />
                    Log
                </a>
            </div>
        </nav>

        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>

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
    <body class="min-h-screen bg-slate-50 text-slate-900">
        <header class="border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex max-w-2xl items-center gap-2 px-4 py-3">
                <div class="flex size-9 items-center justify-center rounded-lg bg-emerald-600 text-white">
                    <flux:icon.clipboard-document-check class="size-5" />
                </div>
                <div>
                    <div class="font-semibold leading-tight text-slate-800">{{ config('app.name') }}</div>
                    <div class="text-xs text-emerald-700">Municipality of Anao, Tarlac</div>
                </div>
            </div>
        </header>
        <main class="mx-auto max-w-2xl px-4 py-6">
            {{ $slot }}
        </main>
        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>

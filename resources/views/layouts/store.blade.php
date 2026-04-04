@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="flex flex-col font-sans antialiased min-h-screen bg-white dark:bg-zinc-900">
        <livewire:store-header />

        <main class="flex-1">
            {{ $slot }}
        </main>

        <livewire:store-footer />

        <x-notification />

        @fluxScripts
    </body>
</html>

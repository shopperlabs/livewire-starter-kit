@props([
    'title' => null,
])

<x-layouts::auth.simple :$title>
    {{ $slot }}
</x-layouts::auth.simple>

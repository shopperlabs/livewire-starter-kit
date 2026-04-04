@blaze

@props([
    'title',
    'variant',
])

@php
    $badgeColor = match ($variant) {
        'info' => 'blue',
        'teal' => 'teal',
        'success' => 'green',
        'danger' => 'rose',
        'warning' => 'yellow',
        'primary' => 'violet',
        'sky' => 'sky',
        'indigo' => 'indigo',
        'orange' => 'orange',
        default => 'zinc',
    };
@endphp

<flux:badge {{ $attributes }} size="sm" :color="$badgeColor" inset="top bottom">{{ $title }}</flux:badge>

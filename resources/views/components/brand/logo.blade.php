@blaze(memo: true)

@props(['name' => config('app.name')])

<span {{ $attributes->class(['inline-flex items-center gap-2 text-black dark:text-white']) }}>
    <x-brand.icon class="size-5 text-accent" aria-hidden="true" />
    <span class="text-lg font-semibold tracking-tight">{{ $name }}</span>
</span>

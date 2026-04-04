@blaze(fold: true)

<div class="rounded-xl ring-1 ring-zinc-200/25 dark:ring-white/10 shadow bg-zinc-50 dark:bg-zinc-900 p-1">
    <div {{ $attributes->twMerge(['class' => 'bg-white rounded-lg ring-1 ring-zinc-200 dark:ring-white/10 dark:bg-zinc-800 p-3']) }}>
        {{ $slot }}
    </div>
</div>

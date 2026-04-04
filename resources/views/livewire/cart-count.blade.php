<span>
    @if($count > 0)
        <span class="absolute -top-1.5 -right-1.5 flex size-4 items-center justify-center rounded-full bg-zinc-900 text-[10px] font-bold text-white dark:bg-white dark:text-zinc-900">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</span>

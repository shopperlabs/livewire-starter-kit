<span>
    @if($count > 0)
        <span class="absolute -top-1.5 -right-1.5 flex size-4 items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
</span>

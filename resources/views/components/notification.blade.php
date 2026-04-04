@blaze

<div
    x-data="{
        shown: false,
        type: 'success',
        message: '',
        timeout: null,
        show(data) {
            this.type = data.type || 'success'
            this.message = data.message || ''
            this.shown = true
            clearTimeout(this.timeout)
            this.timeout = setTimeout(() => this.shown = false, 3000)
        }
    }"
    x-on:notify.window="show($event.detail)"
    x-show="shown"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex justify-center px-4 pb-6 sm:pb-8"
>
    <div class="pointer-events-auto max-w-sm overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-black/5 dark:bg-zinc-800 dark:ring-white/10">
        <div class="flex items-center gap-3 px-4 py-3">
            <template x-if="type === 'success'">
                <div class="flex size-8 items-center justify-center rounded-full bg-green-100">
                    <x-flux::icon.check variant="micro" class="size-4 text-green-600" />
                </div>
            </template>
            <template x-if="type === 'error'">
                <div class="flex size-8 items-center justify-center rounded-full bg-red-100">
                    <x-flux::icon.x-mark variant="micro" class="size-4 text-red-600" />
                </div>
            </template>
            <p x-text="message" class="text-sm font-medium text-zinc-900 dark:text-white"></p>
            <button @click="shown = false" class="ml-auto -mr-1 shrink-0 rounded-md p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                <span class="sr-only">{{ __('Close') }}</span>
                <x-flux::icon.x-mark variant="micro" class="size-4" />
            </button>
        </div>
    </div>
</div>

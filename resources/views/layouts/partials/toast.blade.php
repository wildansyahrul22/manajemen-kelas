<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'success', message: toast.message ?? '' });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:notify.window="add($event.detail)"
    @if (session()->has('notify'))
        x-init="add(@js(session('notify')))"
    @endif
    class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-center gap-2 sm:inset-x-auto sm:right-6 sm:items-end"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border bg-white p-4 shadow-lg shadow-slate-900/5"
            :class="{
                'border-emerald-200': toast.type === 'success',
                'border-rose-200': toast.type === 'error',
                'border-amber-200': toast.type === 'warning',
            }"
        >
            <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-emerald-100 text-emerald-600': toast.type === 'success',
                    'bg-rose-100 text-rose-600': toast.type === 'error',
                    'bg-amber-100 text-amber-600': toast.type === 'warning',
                }"
            >
                <template x-if="toast.type === 'success'"><x-heroicon-m-check class="size-4" /></template>
                <template x-if="toast.type === 'error'"><x-heroicon-m-x-mark class="size-4" /></template>
                <template x-if="toast.type === 'warning'"><x-heroicon-m-exclamation-triangle class="size-4" /></template>
            </span>
            <p class="flex-1 text-sm text-slate-700" x-text="toast.message"></p>
            <button type="button" x-on:click="remove(toast.id)" class="text-slate-400 hover:text-slate-600" aria-label="Tutup">
                <x-heroicon-m-x-mark class="size-4" />
            </button>
        </div>
    </template>
</div>

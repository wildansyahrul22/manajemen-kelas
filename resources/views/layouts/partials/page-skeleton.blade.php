{{-- Shown in place of the page content from the moment a wire:navigate visit starts until the new page arrives. --}}
<div data-page-skeleton class="hidden animate-pulse" aria-hidden="true">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0 flex-1">
            <div class="h-7 w-48 max-w-full rounded-lg bg-slate-200"></div>
            <div class="mt-2.5 h-4 w-72 max-w-full rounded bg-slate-200/80"></div>
        </div>
        <div class="h-10 w-36 rounded-xl bg-slate-200"></div>
    </div>

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="h-10 w-full rounded-xl bg-slate-200/80 lg:max-w-xs"></div>
        <div class="h-10 w-full rounded-xl bg-slate-200/60 sm:w-56"></div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @for ($i = 0; $i < 6; $i++)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.03]">
                <div class="h-3 w-24 rounded bg-slate-200/80"></div>
                <div class="mt-3 h-5 w-2/3 rounded bg-slate-200"></div>
                <div class="mt-3 h-3 w-full rounded bg-slate-200/70"></div>
                <div class="mt-2 h-3 w-4/5 rounded bg-slate-200/70"></div>
                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                    <div class="flex -space-x-2">
                        <div class="size-7 rounded-full bg-slate-200 ring-2 ring-white"></div>
                        <div class="size-7 rounded-full bg-slate-200 ring-2 ring-white"></div>
                        <div class="size-7 rounded-full bg-slate-200 ring-2 ring-white"></div>
                    </div>
                    <div class="h-3 w-16 rounded bg-slate-200/80"></div>
                </div>
            </div>
        @endfor
    </div>
</div>

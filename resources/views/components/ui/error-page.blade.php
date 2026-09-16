{{--
    Full page shown for HTTP errors (resources/views/errors/*). Plain, non-technical wording only:
    what happened and what the user can do next.
--}}
@props(['kode', 'judul', 'pesan', 'icon' => 'heroicon-o-exclamation-triangle'])
<x-layouts::guest :title="$judul">
    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-10 text-center shadow-sm sm:px-10">
        <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
            <x-dynamic-component :component="$icon" class="size-8" />
        </span>
        <p class="mt-6 text-xs font-semibold uppercase tracking-wider text-slate-400">Kode {{ $kode }}</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $judul }}</h1>
        <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-slate-500">{{ $pesan }}</p>

        <div class="mt-8 flex flex-col justify-center gap-2 sm:flex-row">
            {{ $slot }}
        </div>
    </div>
    <p class="mt-6 text-center text-xs text-slate-400">Jika masalah berlanjut, hubungi admin kelas Anda.</p>
</x-layouts::guest>

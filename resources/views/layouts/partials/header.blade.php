<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button type="button" x-on:click="toggleSidebar()" class="-ml-1 rounded-lg p-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Buka/tutup menu">
            <x-heroicon-o-bars-3 class="size-6" />
        </button>

        <h1 data-page-title class="truncate text-base font-semibold text-slate-900 sm:text-lg">{{ $title ?? config('app.name') }}</h1>

        <div class="ml-auto flex items-center gap-2 sm:gap-3">
            @if ($user->isSuperAdmin())
                <livewire:layout.kelas-filter />
            @elseif ($kelasAktif)
                <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm sm:flex">
                    <x-heroicon-o-building-library class="size-4 text-slate-500" />
                    <span class="font-semibold text-slate-800">{{ $kelasAktif->nama }}</span>
                    <span class="text-slate-300">·</span>
                    <span class="text-slate-600">{{ $kelasAktif->semesterAktif->nama }}</span>
                </div>
            @endif

            <x-ui.dropdown align="right">
                <x-slot:trigger>
                    <button type="button" class="flex items-center gap-2 rounded-xl p-1 transition hover:bg-slate-100">
                        <x-ui.avatar :name="$user->name" size="sm" />
                        <x-heroicon-o-chevron-down class="hidden size-4 text-slate-400 sm:block" />
                    </button>
                </x-slot:trigger>

                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $user->npm }} · {{ $user->role->label() }}</p>
                </div>
                <div class="p-1.5">
                    <x-ui.menu-item :href="route('profile.edit')" icon="heroicon-o-user-circle">Profil Saya</x-ui.menu-item>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.menu-item type="submit" icon="heroicon-o-arrow-right-start-on-rectangle" danger>Keluar</x-ui.menu-item>
                    </form>
                </div>
            </x-ui.dropdown>
        </div>
    </div>
</header>

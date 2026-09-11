@php
    $canManage = $user->isAdmin() || $user->isSuperAdmin();
@endphp
<aside
    class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-[width,transform] duration-200 ease-in-out lg:translate-x-0"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': ! sidebarOpen,
        'lg:w-20': sidebarCollapsed,
    }"
>
    <div class="flex h-16 items-center gap-3 px-4" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : 'px-5'">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3" :title="sidebarCollapsed ? 'Manajemen Kelas' : null">
            <x-ui.logo />
            <span class="truncate text-base font-bold tracking-tight text-slate-900" :class="sidebarCollapsed && 'lg:hidden'">Manajemen Kelas</span>
        </a>
        <button type="button" x-on:click="sidebarOpen = false" class="ml-auto rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Tutup menu">
            <x-heroicon-o-x-mark class="size-5" />
        </button>
        <button type="button" x-on:click="sidebarCollapsed = true" x-show="! sidebarCollapsed" class="ml-auto hidden rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:block" aria-label="Kecilkan sidebar" title="Kecilkan sidebar">
            <x-heroicon-o-chevron-double-left class="size-5" />
        </button>
    </div>

    <nav class="scrollbar-thin flex-1 space-y-6 overflow-y-auto px-4 py-4" :class="sidebarCollapsed && 'lg:space-y-2 lg:px-3'">
        <div>
            <x-ui.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="heroicon-o-squares-2x2">Dashboard</x-ui.nav-link>
        </div>

        <div>
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" :class="sidebarCollapsed && 'lg:hidden'">Akademik</p>
            <div x-show="sidebarCollapsed" x-cloak class="mx-2 mb-2 hidden border-t border-slate-200 lg:block"></div>
            <div class="space-y-1">
                <x-ui.nav-link :href="route('tugas.index')" :active="request()->routeIs('tugas.*')" icon="heroicon-o-clipboard-document-list">Daftar Tugas</x-ui.nav-link>
                <x-ui.nav-link :href="route('jadwal.index')" :active="request()->routeIs('jadwal.*')" icon="heroicon-o-calendar-days">Jadwal Kelas</x-ui.nav-link>
                <x-ui.nav-link :href="route('mata-kuliah.index')" :active="request()->routeIs('mata-kuliah.*')" icon="heroicon-o-book-open">Mata Kuliah</x-ui.nav-link>
                <x-ui.nav-link :href="route('kelompok.index')" :active="request()->routeIs('kelompok.*')" icon="heroicon-o-user-group">Kelompok</x-ui.nav-link>
            </div>
        </div>

        <div>
            <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" :class="sidebarCollapsed && 'lg:hidden'">Informasi</p>
            <div x-show="sidebarCollapsed" x-cloak class="mx-2 mb-2 hidden border-t border-slate-200 lg:block"></div>
            <div class="space-y-1">
                <x-ui.nav-link :href="route('informasi.index')" :active="request()->routeIs('informasi.*')" icon="heroicon-o-megaphone">Daftar Informasi</x-ui.nav-link>
                <x-ui.nav-link :href="route('kategori-informasi.index')" :active="request()->routeIs('kategori-informasi.*')" icon="heroicon-o-tag">Kategori Informasi</x-ui.nav-link>
            </div>
        </div>

        @if ($canManage)
            <div>
                <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" :class="sidebarCollapsed && 'lg:hidden'">Manajemen</p>
                <div x-show="sidebarCollapsed" x-cloak class="mx-2 mb-2 hidden border-t border-slate-200 lg:block"></div>
                <div class="space-y-1">
                    <x-ui.nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="heroicon-o-users">Users</x-ui.nav-link>
                    @if ($user->isSuperAdmin())
                        <x-ui.nav-link :href="route('kelas.index')" :active="request()->routeIs('kelas.*')" icon="heroicon-o-building-library">Kelas</x-ui.nav-link>
                    @endif
                    <x-ui.nav-link :href="route('semester-aktif.index')" :active="request()->routeIs('semester-aktif.*')" icon="heroicon-o-adjustments-horizontal">Semester Aktif</x-ui.nav-link>
                </div>
            </div>
        @endif
    </nav>

    <div class="border-t border-slate-200 p-4" :class="sidebarCollapsed && 'lg:p-3'">
        <button type="button" x-on:click="sidebarCollapsed = false" x-show="sidebarCollapsed" x-cloak class="mb-2 hidden w-full items-center justify-center rounded-xl py-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:flex" aria-label="Lebarkan sidebar" title="Lebarkan sidebar">
            <x-heroicon-o-chevron-double-right class="size-5" />
        </button>
        <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-50" :class="sidebarCollapsed && 'lg:justify-center lg:p-1'" :title="sidebarCollapsed ? @js($user->name) : null">
            <x-ui.avatar :name="$user->name" />
            <div class="min-w-0 flex-1" :class="sidebarCollapsed && 'lg:hidden'">
                <p class="truncate text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user->role->label() }}</p>
            </div>
            <x-heroicon-o-chevron-right class="size-4 text-slate-400" ::class="sidebarCollapsed && 'lg:hidden'" />
        </a>
    </div>
</aside>

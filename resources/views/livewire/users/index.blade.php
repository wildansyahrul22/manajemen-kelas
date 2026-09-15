@php $actor = auth()->user(); @endphp
<div>
    <x-ui.page-header title="Users" :description="$actor->isSuperAdmin()
        ? 'Kelola akun mahasiswa, admin kelas, dan super admin.'
        : 'Kelola akun mahasiswa dan admin kelas ' . $this->kelas->nama . '.'">
        <x-slot:actions>
            <x-ui.export-button />
            <x-ui.button wire:click="openCreate" opens="showForm"><x-heroicon-m-plus class="size-4" /> Tambah User</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :padding="false">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center">
            <div class="flex-1 lg:max-w-xs">
                <x-ui.search wire:model.live.debounce.500ms="search" placeholder="Cari nama / NPM..." />
            </div>
            <x-ui.select name="role" wire:model.live="role" :options="$this->roleOptions" placeholder="Semua role" clearable
                class="sm:w-48" />
            @if ($role !== \App\Enums\Role::SuperAdmin->value)
                <x-ui.checkbox label="Tampilkan kelas terbang semester lain" name="semuaSemester" wire:model.live="semuaSemester"
                    description="Default: hanya anggota {{ $this->kelas->semesterAktif->nama }}." />
            @endif
            <div class="lg:ml-auto">
                <x-ui.per-page wire:model.live="perPage" />
            </div>
        </div>

        <div wire:loading.class="opacity-50" wire:target="search, role, semuaSemester, perPage, gotoPage, nextPage, previousPage"
            class="transition-opacity">
            @if ($this->daftarUsers->isEmpty())
                <x-ui.empty-state title="User tidak ditemukan" description="Tidak ada user yang cocok dengan filter."
                    icon="heroicon-o-users" />
            @else
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Nama</x-ui.th>
                        <x-ui.th>NPM</x-ui.th>
                        <x-ui.th class="hidden md:table-cell">No. HP</x-ui.th>
                        <x-ui.th>Role</x-ui.th>
                        @if ($actor->isSuperAdmin())
                            <x-ui.th class="hidden lg:table-cell">Kelas</x-ui.th>
                        @endif
                        <x-ui.th class="text-right">Aksi</x-ui.th>
                    </x-slot:head>
                    @foreach ($this->daftarUsers as $user)
                        <tr wire:key="user-{{ $user->id }}" class="transition hover:bg-slate-50/70">
                            <x-ui.td>
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$user->name" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('users.show', $user) }}" wire:navigate
                                            class="block truncate font-semibold text-slate-800 hover:text-primary-900 hover:underline">{{ $user->name }}</a>
                                        <p class="text-xs text-slate-500 md:hidden">{{ $user->noHpFormatted() ?? '—' }}</p>
                                    </div>
                                </div>
                            </x-ui.td>
                            <x-ui.td class="font-mono text-xs text-slate-600">{{ $user->npm }}</x-ui.td>
                            <x-ui.td
                                class="hidden whitespace-nowrap text-slate-600 md:table-cell">{{ $user->noHpFormatted() ?? '—' }}</x-ui.td>
                            <x-ui.td>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-ui.badge :class="$user->role->badgeClass()">{{ $user->role->label() }}</x-ui.badge>
                                    @if ($user->isKelasTerbang())
                                        @php $aktifSekarang = $user->kelas_id === $this->kelas->id && $user->aktifPadaSemester($this->kelas->semester_aktif_id); @endphp
                                        <x-ui.badge :color="$aktifSekarang ? 'amber' : 'slate'" :title="$aktifSekarang ? 'Anggota kelas terbang pada semester aktif' : 'Tidak aktif pada semester ini'">
                                            <x-heroicon-m-paper-airplane class="size-3" /> Kelas terbang · {{ $user->semesterKelasTerbang->nama }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </x-ui.td>
                            @if ($actor->isSuperAdmin())
                                <x-ui.td
                                    class="hidden text-slate-600 lg:table-cell">{{ $user->kelas?->nama ?? '—' }}</x-ui.td>
                            @endif
                            <x-ui.td class="text-right">
                                <x-ui.action-menu>
                                    <x-ui.menu-item :href="route('users.show', $user)" icon="heroicon-o-eye">Lihat
                                        detail</x-ui.menu-item>
                                    @can('update', $user)
                                        <x-ui.menu-item wire:click="openEdit({{ $user->id }})" opens="showForm"
                                            icon="heroicon-o-pencil-square">Edit</x-ui.menu-item>
                                    @endcan
                                    @can('delete', $user)
                                        <x-ui.menu-item wire:click="confirmDelete({{ $user->id }})" opens="confirmingDelete"
                                            icon="heroicon-o-trash" danger>Hapus</x-ui.menu-item>
                                    @endcan
                                </x-ui.action-menu>
                            </x-ui.td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </div>

        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $this->daftarUsers->links() }}
        </div>
    </x-ui.card>

    <x-ui.modal model="showForm" :title="$form->user ? 'Edit User' : 'Tambah User'" max-width="max-w-2xl" loading="openCreate, openEdit">
        <form id="form-user" wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input label="NPM" name="form.npm" wire:model="form.npm" placeholder="6-20 karakter" required
                    hint="Digunakan untuk login." />
                <x-ui.input label="Nama lengkap" name="form.name" wire:model="form.name"
                    placeholder="Maks. 100 karakter" required />
                <x-ui.input label="No. HP" name="form.no_hp" wire:model="form.no_hp" placeholder="6281234567890 (opsional)"
                    inputmode="numeric" hint="Opsional. Format Indonesia (62), 12-14 digit; 08xx otomatis diubah." />
                <x-ui.select label="Role" name="form.role" wire:model.live="form.role" :options="$this->roleOptions"
                    placeholder="Pilih role" required />
                @if ($actor->isSuperAdmin() && $form->role !== \App\Enums\Role::SuperAdmin->value)
                    <x-ui.combobox label="Kelas" name="form.kelas_id" wire:model="form.kelas_id" :options="$this->kelasOptions->pluck('nama', 'id')"
                        placeholder="Pilih kelas" required />
                @elseif (!$actor->isSuperAdmin())
                    <x-ui.input label="Kelas" name="kelas_readonly" :value="$this->kelas->nama" disabled />
                @endif
            </div>

            @if ($form->role !== \App\Enums\Role::SuperAdmin->value)
                <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                    <x-ui.checkbox label="Kelas terbang" name="form.kelas_terbang" wire:model.live="form.kelas_terbang"
                        description="Mahasiswa dari kelas lain yang hanya ikut kelas ini pada satu semester. Ia hanya dihitung sebagai anggota kelas (dashboard, pilihan anggota kelompok, daftar user) saat semester itu aktif." />
                    @if ($form->kelas_terbang)
                        <x-ui.combobox label="Semester kelas terbang" name="form.kelas_terbang_semester_id" wire:model="form.kelas_terbang_semester_id"
                            :options="$this->semesterOptions->pluck('nama', 'id')" placeholder="Pilih semester" required class="sm:w-64"
                            hint="Hanya pada semester ini user tersebut tampil sebagai anggota kelas." />
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
                <x-ui.input :label="$form->user ? 'Password baru' : 'Password'" name="form.password" type="password" wire:model="form.password"
                    autocomplete="new-password" :required="!$form->user" :hint="$form->user ? 'Kosongkan jika tidak ingin mengubah password.' : 'Minimal 8 karakter.'" />
                <x-ui.input label="Konfirmasi password" name="form.password_confirmation" type="password"
                    wire:model="form.password_confirmation" autocomplete="new-password" :required="!$form->user" />
            </div>
        </form>
        <x-slot:footer>
            <x-ui.form-actions form="form-user" :label="$form->user ? 'Simpan Perubahan' : 'Tambah User'" />
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.confirm model="confirmingDelete" title="Hapus user?"
        description="Akun akan dihapus permanen beserta keanggotaan kelompoknya." />
</div>

<div>
    <x-ui.page-header title="Profil Saya" description="Perbarui data diri dan password akun Anda." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:self-start">
            <div class="flex flex-col items-center text-center">
                <x-ui.avatar :name="$user->name" size="lg" />
                <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                <x-ui.badge :class="$user->role->badgeClass()" class="mt-2">{{ $user->role->label() }}</x-ui.badge>
            </div>
            <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5 text-sm">
                <div><dt class="text-slate-500">NPM</dt><dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $user->npm }}</dd></div>
                <div><dt class="text-slate-500">Kelas</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $user->kelas?->nama ?? 'Semua kelas' }}</dd></div>
                <div><dt class="text-slate-500">Bergabung</dt><dd class="mt-0.5 text-slate-700">{{ $user->created_at->isoFormat('D MMMM YYYY') }}</dd></div>
            </dl>
        </x-ui.card>

        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Data Diri" description="Nama dan nomor HP yang tampil untuk anggota kelas.">
                <form wire:submit="updateProfile" class="space-y-4">
                    <x-ui.input label="Nama lengkap" name="name" wire:model="name" required />
                    <x-ui.input label="No. HP" name="no_hp" wire:model="no_hp" inputmode="numeric" placeholder="Opsional" hint="Opsional. Format Indonesia (62), 12-14 digit; 08xx otomatis diubah." />
                    <div class="flex justify-end">
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateProfile">
                            <x-heroicon-m-arrow-path class="size-4 animate-spin" wire:loading wire:target="updateProfile" /> Simpan
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card title="Ubah Password" description="Gunakan minimal 8 karakter.">
                <form wire:submit="updatePassword" class="space-y-4">
                    <x-ui.input label="Password saat ini" name="current_password" type="password" wire:model="current_password" autocomplete="current-password" required />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input label="Password baru" name="password" type="password" wire:model="password" autocomplete="new-password" required />
                        <x-ui.input label="Konfirmasi password baru" name="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required />
                    </div>
                    <div class="flex justify-end">
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                            <x-heroicon-m-arrow-path class="size-4 animate-spin" wire:loading wire:target="updatePassword" /> Ubah Password
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</div>

<div>
    <div class="mb-8 flex flex-col items-center text-center">
        <x-ui.logo size="size-20" class="rounded-2xl shadow-lg" />
        <h1 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Manajemen Kelas</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk dengan NPM dan password Anda.</p>
    </div>

    <x-ui.card class="p-6 sm:p-8" :padding="false">
        <form wire:submit="login" class="space-y-5">
            <x-ui.input label="NPM" name="npm" wire:model="npm" placeholder="Contoh: 202343502484"
                autocomplete="username" autofocus required />

            <x-ui.input label="Password" name="password" type="password" wire:model="password" placeholder="••••••••"
                autocomplete="current-password" required />

            <x-ui.checkbox label="Ingat saya" name="remember" wire:model="remember" />

            <x-ui.button type="submit" class="w-full" wire:loading.attr="disabled">
                <x-heroicon-m-arrow-path class="size-4 animate-spin" wire:loading wire:target="login" />
                Masuk
            </x-ui.button>
        </form>
    </x-ui.card>

    <p class="mt-6 text-center text-xs text-slate-400">Lupa password? Hubungi admin kelas Anda.</p>
</div>

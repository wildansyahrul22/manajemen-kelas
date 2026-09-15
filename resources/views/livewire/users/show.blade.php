<div>
    <x-ui.page-header :title="$user->name" :description="$user->role->label()" :back="route('users.index')" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:self-start">
            <div class="flex flex-col items-center text-center">
                <x-ui.avatar :name="$user->name" size="lg" />
                <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $user->name }}</h3>
                <x-ui.badge :class="$user->role->badgeClass()" class="mt-2">{{ $user->role->label() }}</x-ui.badge>
            </div>
            <dl class="mt-6 space-y-4 border-t border-slate-100 pt-5 text-sm">
                <div><dt class="text-slate-500">NPM</dt><dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $user->npm }}</dd></div>
                <div><dt class="text-slate-500">No. HP</dt><dd class="mt-0.5 font-medium text-slate-800">
                    @if ($user->no_hp)
                        <a href="{{ $user->whatsappUrl() }}" target="_blank" rel="noopener" class="hover:text-primary-900 hover:underline">{{ $user->noHpFormatted() }}</a>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </dd></div>
                <div><dt class="text-slate-500">Kelas</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $user->kelas?->nama ?? '—' }}</dd></div>
                @if ($user->isKelasTerbang())
                    <div><dt class="text-slate-500">Keanggotaan</dt><dd class="mt-0.5 font-medium text-slate-800">Kelas terbang · {{ $user->semesterKelasTerbang->nama }}</dd></div>
                @endif
                <div><dt class="text-slate-500">Terdaftar</dt><dd class="mt-0.5 text-slate-700">{{ $user->created_at->isoFormat('D MMMM YYYY') }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Keanggotaan Kelompok" :padding="false" class="lg:col-span-2">
            @forelse ($this->kelompok as $kelompok)
                <a href="{{ route('kelompok.show', $kelompok) }}" wire:navigate wire:key="kelompok-{{ $kelompok->id }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 transition last:border-b-0 hover:bg-slate-50 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ $kelompok->nama }}</p>
                        <p class="truncate text-xs text-slate-500">{{ $kelompok->mataKuliah->nama }}</p>
                    </div>
                    @if ($kelompok->pivot->is_ketua)<x-ui.badge color="primary">Ketua</x-ui.badge>@endif
                </a>
            @empty
                <x-ui.empty-state title="Belum tergabung di kelompok" icon="heroicon-o-user-group" class="py-10" />
            @endforelse
        </x-ui.card>
    </div>
</div>

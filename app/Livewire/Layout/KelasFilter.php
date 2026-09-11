<?php

namespace App\Livewire\Layout;

use App\Models\Kelas;
use App\Support\KelasContext;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Header dropdown that lets a super admin switch the kelas every page is scoped to.
 */
class KelasFilter extends Component
{
    public string $kelasId = '';

    public function mount(KelasContext $context): void
    {
        $this->kelasId = (string) ($context->id() ?? '');
    }

    #[Computed]
    public function daftarKelas(): Collection
    {
        return Kelas::query()->orderBy('nama')->get(['id', 'nama']);
    }

    public function updatedKelasId(string $value, KelasContext $context): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $kelas = Kelas::query()->findOrFail((int) $value);

        $context->switchTo($kelas);

        $this->redirect(request()->header('referer') ?: route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.layout.kelas-filter');
    }
}

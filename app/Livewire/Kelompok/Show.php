<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Models\Kelompok;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithKelas, ManagesKelompok, Notifies;

    public Kelompok $kelompok;

    public function mount(Kelompok $kelompok): void
    {
        $this->authorize('view', $kelompok);

        $this->kelompok = $kelompok;
    }

    #[Computed]
    public function anggota(): Collection
    {
        return $this->kelompok->anggota()->get(['users.id', 'users.npm', 'users.name', 'users.no_hp']);
    }

    protected function afterSave(Kelompok $kelompok): void
    {
        $this->kelompok = $kelompok->fresh();
        unset($this->anggota);
    }

    protected function afterDelete(): void
    {
        $this->flashNotify('Kelompok berhasil dihapus.');
        $this->redirectRoute('kelompok.index', navigate: true);
    }

    public function render()
    {
        $this->kelompok->loadMissing(['mataKuliah:id,kelas_id,nama,dosen', 'kategori:id,mata_kuliah_id,nama,final', 'creator:id,name']);

        return view('livewire.kelompok.show')->title($this->kelompok->nama);
    }
}

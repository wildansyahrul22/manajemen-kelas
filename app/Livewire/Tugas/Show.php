<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Models\Tugas;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithKelas, ManagesTugas, Notifies;

    public Tugas $tugas;

    public function mount(Tugas $tugas): void
    {
        $this->authorize('view', $tugas);

        $this->tugas = $tugas;
    }

    protected function afterSave(Tugas $tugas): void
    {
        $this->tugas = $tugas->fresh();
    }

    protected function afterDelete(): void
    {
        $this->flashNotify('Tugas berhasil dihapus.');
        $this->redirectRoute('tugas.index', navigate: true);
    }

    public function render()
    {
        $this->tugas->loadMissing(['mataKuliah:id,kelas_id,kode,nama,dosen,sks', 'creator:id,name']);

        return view('livewire.tugas.show')->title($this->tugas->nama);
    }
}

<?php

namespace App\Livewire\Informasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Models\Informasi;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithKelas, ManagesInformasi, Notifies;

    public Informasi $informasi;

    public function mount(Informasi $informasi): void
    {
        $this->authorize('view', $informasi);

        $this->informasi = $informasi;
    }

    protected function afterSave(Informasi $informasi): void
    {
        $this->informasi = $informasi->fresh();
    }

    protected function afterDelete(): void
    {
        $this->flashNotify('Informasi berhasil dihapus.');
        $this->redirectRoute('informasi.index', navigate: true);
    }

    public function render()
    {
        $this->informasi->loadMissing(['kategori:id,nama,warna', 'creator:id,name,role']);

        return view('livewire.informasi.show')->title($this->informasi->judul);
    }
}

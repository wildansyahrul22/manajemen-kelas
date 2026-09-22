<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Models\Kelompok;
use App\Models\Tugas;
use App\Support\PesanWhatsApp;
use Livewire\Attributes\Computed;
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

    /**
     * For a tugas kelompok: the kelompok (with anggota) the current user belongs to in that kategori.
     */
    #[Computed]
    public function kelompokSaya(): ?Kelompok
    {
        if (! $this->tugas->isTugasKelompok()) {
            return null;
        }

        return Kelompok::query()
            ->where('kategori_kelompok_id', $this->tugas->kategori_kelompok_id)
            ->whereHas('anggota', fn ($query) => $query->whereKey(auth()->id()))
            ->with(['anggota' => fn ($query) => $query->select(['users.id', 'users.npm', 'users.name'])])
            ->first();
    }

    #[Computed]
    public function jumlahKelompok(): int
    {
        return $this->tugas->isTugasKelompok()
            ? Kelompok::query()->where('kategori_kelompok_id', $this->tugas->kategori_kelompok_id)->count()
            : 0;
    }

    #[Computed]
    public function teksWhatsApp(): string
    {
        return PesanWhatsApp::tugas($this->tugas->loadMissing(['mataKuliah:id,ulid,kelas_id,kode,nama,dosen,sks', 'kategoriKelompok:id,nama', 'lampiran']), $this->kelas);
    }

    protected function afterSave(Tugas $tugas): void
    {
        $this->tugas = $tugas->fresh();
        unset($this->teksWhatsApp, $this->kelompokSaya, $this->jumlahKelompok);
    }

    protected function afterDelete(): void
    {
        $this->flashNotify('Tugas berhasil dihapus.');
        $this->redirectRoute('tugas.index', navigate: true);
    }

    public function render()
    {
        $this->tugas->loadMissing(['mataKuliah:id,ulid,kelas_id,kode,nama,dosen,sks', 'creator:id,name', 'kategoriKelompok:id,nama', 'lampiran']);

        return view('livewire.tugas.show')->title($this->tugas->nama);
    }
}

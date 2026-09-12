<?php

namespace App\Livewire\MataKuliah;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Models\MataKuliah;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithKelas, ManagesMataKuliah, Notifies;

    public MataKuliah $mataKuliah;

    public function mount(MataKuliah $mataKuliah): void
    {
        $this->authorize('view', $mataKuliah);

        $this->mataKuliah = $mataKuliah;
    }

    #[Computed]
    public function jadwal(): Collection
    {
        return $this->mataKuliah->jadwal()
            ->select(['id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])
            ->orderBy('hari')->orderBy('jam_mulai')
            ->get();
    }

    #[Computed]
    public function tugas(): Collection
    {
        return $this->mataKuliah->tugas()
            ->select(['id', 'mata_kuliah_id', 'nama', 'deadline'])
            ->orderByDesc('deadline')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function kelompok(): Collection
    {
        return $this->mataKuliah->kelompok()
            ->select(['id', 'kategori_kelompok_id', 'nama'])
            ->with('kategori:id,nama')
            ->withCount('anggota')
            ->orderBy('kategori_kelompok_id')
            ->orderBy('nama')
            ->get();
    }

    protected function afterSave(MataKuliah $mataKuliah): void
    {
        $this->mataKuliah = $mataKuliah->fresh();
    }

    protected function afterDelete(): void
    {
        $this->flashNotify('Mata kuliah berhasil dihapus.');
        $this->redirectRoute('mata-kuliah.index', navigate: true);
    }

    public function render()
    {
        $this->mataKuliah->loadMissing('semester:id,nama');

        return view('livewire.mata-kuliah.show')->title($this->mataKuliah->nama);
    }
}

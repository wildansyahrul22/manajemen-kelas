<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Kelompok')]
class Index extends Component
{
    use InteractsWithKelas, ManagesKelompok, Notifies, WithTableControls;

    #[Url(as: 'mk', except: '')]
    public string $mataKuliahId = '';

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    public function updatedMataKuliahId(): void
    {
        $this->kategoriId = '';
        $this->resetPage();
    }

    public function updatedKategoriId(): void
    {
        $this->resetPage();
    }

    /**
     * Kategori filter options, narrowed to the chosen mata kuliah when one is selected.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function kategoriFilterOptions(): Collection
    {
        return KategoriKelompok::query()
            ->select(['id', 'mata_kuliah_id', 'nama'])
            ->with('mataKuliah:id,nama')
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->get()
            ->sortBy([['mataKuliah.nama', 'asc'], ['nama', 'asc']])
            ->mapWithKeys(fn (KategoriKelompok $kategori) => [
                $kategori->id => $this->mataKuliahId !== '' ? $kategori->nama : $kategori->nama.' · '.$kategori->mataKuliah->nama,
            ]);
    }

    #[Computed]
    public function daftarKelompok(): LengthAwarePaginator
    {
        return Kelompok::query()
            ->select(['id', 'mata_kuliah_id', 'kategori_kelompok_id', 'nama', 'deskripsi', 'created_by'])
            ->with([
                'mataKuliah:id,kelas_id,nama',
                'kategori:id,nama',
                'anggota' => fn ($query) => $query->select(['users.id', 'users.name']),
            ])
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->when($this->kategoriId !== '', fn ($query) => $query->where('kategori_kelompok_id', (int) $this->kategoriId))
            ->search($this->search)
            ->orderBy('mata_kuliah_id')
            ->orderBy('kategori_kelompok_id')
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    protected function afterSave(Kelompok $kelompok): void
    {
        unset($this->daftarKelompok);
        unset($this->kategoriFilterOptions);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarKelompok);
    }

    public function render()
    {
        return view('livewire.kelompok.index');
    }
}

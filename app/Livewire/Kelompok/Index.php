<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Kelompok;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function updatedMataKuliahId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function daftarKelompok(): LengthAwarePaginator
    {
        return Kelompok::query()
            ->select(['id', 'mata_kuliah_id', 'nama', 'deskripsi', 'created_by'])
            ->with([
                'mataKuliah:id,kelas_id,nama',
                'anggota' => fn ($query) => $query->select(['users.id', 'users.name']),
            ])
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->search($this->search)
            ->orderBy('mata_kuliah_id')
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    protected function afterSave(Kelompok $kelompok): void
    {
        unset($this->daftarKelompok);
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

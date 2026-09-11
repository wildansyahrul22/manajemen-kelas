<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Tugas;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Daftar Tugas')]
class Index extends Component
{
    use InteractsWithKelas, ManagesTugas, Notifies, WithTableControls;

    #[Url(as: 'mk', except: '')]
    public string $mataKuliahId = '';

    #[Url(except: '')]
    public string $status = '';

    public function updatedMataKuliahId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function daftarTugas(): LengthAwarePaginator
    {
        $now = now()->toDateTimeString();

        return Tugas::query()
            ->select(['id', 'mata_kuliah_id', 'nama', 'deadline', 'created_at', 'updated_at'])
            ->with('mataKuliah:id,nama,dosen')
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->when($this->status === 'aktif', fn ($query) => $query->belumDeadline())
            ->when($this->status === 'lewat', fn ($query) => $query->lewatDeadline())
            ->search($this->search)
            // Upcoming deadlines first (nearest on top), then past ones (most recent on top).
            ->orderByRaw('case when deadline >= ? then 0 else 1 end', [$now])
            ->orderByRaw('case when deadline >= ? then deadline else null end asc', [$now])
            ->orderByDesc('deadline')
            ->paginate($this->perPage());
    }

    protected function afterSave(Tugas $tugas): void
    {
        unset($this->daftarTugas);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarTugas);
    }

    public function render()
    {
        return view('livewire.tugas.index');
    }
}

<?php

namespace App\Livewire\MataKuliah;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\MataKuliah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Mata Kuliah')]
class Index extends Component
{
    use InteractsWithKelas, ManagesMataKuliah, Notifies, WithTableControls;

    /**
     * Semester being viewed; empty means the kelas' active semester.
     */
    #[Url(as: 'semester', except: '')]
    public string $semesterId = '';

    public function updatedSemesterId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function semesterDipilih(): int
    {
        return $this->semesterId !== '' ? (int) $this->semesterId : $this->kelas->semester_aktif_id;
    }

    #[Computed]
    public function daftarMataKuliah(): LengthAwarePaginator
    {
        return MataKuliah::query()
            ->select(['id', 'semester_id', 'kode', 'nama', 'dosen', 'sks'])
            ->withCount(['jadwal', 'jadwalLab', 'tugas'])
            ->where('kelas_id', $this->kelas->id)
            ->where('semester_id', $this->semesterDipilih)
            ->search($this->search)
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    protected function afterSave(MataKuliah $mataKuliah): void
    {
        unset($this->daftarMataKuliah);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarMataKuliah);
    }

    public function render()
    {
        return view('livewire.mata-kuliah.index');
    }
}

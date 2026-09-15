<?php

namespace App\Livewire\MataKuliah;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\MataKuliah;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Mata kuliah of the viewed semester narrowed by the search box, with related counts.
     */
    protected function mataKuliahQuery(): Builder
    {
        return MataKuliah::query()
            ->select(['id', 'semester_id', 'kode', 'nama', 'dosen', 'sks'])
            ->withCount(['jadwal', 'jadwalLab', 'tugas'])
            ->where('kelas_id', $this->kelas->id)
            ->where('semester_id', $this->semesterDipilih)
            ->search($this->search)
            ->orderBy('nama');
    }

    #[Computed]
    public function daftarMataKuliah(): LengthAwarePaginator
    {
        return $this->mataKuliahQuery()->paginate($this->perPage());
    }

    public function export(): StreamedResponse
    {
        $semester = $this->semesterOptions->firstWhere('id', $this->semesterDipilih);

        $baris = $this->mataKuliahQuery()
            ->get()
            ->map(fn (MataKuliah $mataKuliah, int $indeks) => [
                $indeks + 1,
                $mataKuliah->kode,
                $mataKuliah->nama,
                $mataKuliah->dosen,
                $mataKuliah->sks,
                $semester?->nama,
                $mataKuliah->jadwal_count,
                $mataKuliah->jadwal_lab_count,
                $mataKuliah->tugas_count,
            ]);

        return ExcelExport::buat('Mata Kuliah')
            ->subjudul('Kelas '.$this->kelas->nama.' · '.($semester?->nama ?? 'Semester aktif'))
            ->filter([
                'Pencarian' => $this->search,
                'Semester' => $this->semesterId !== '' ? $semester?->nama : null,
            ])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Kode',
                'Nama Mata Kuliah',
                'Dosen',
                ['SKS', ExcelExport::TIPE_ANGKA],
                'Semester',
                ['Jadwal Kelas', ExcelExport::TIPE_ANGKA],
                ['Jadwal Lab', ExcelExport::TIPE_ANGKA],
                ['Tugas', ExcelExport::TIPE_ANGKA],
            )
            ->baris($baris)
            ->unduh('mata-kuliah '.$this->kelas->nama);
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

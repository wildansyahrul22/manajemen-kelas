<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Tugas;
use App\Models\TugasLampiran;
use App\Support\ExcelExport;
use App\Support\PesanWhatsApp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Tugas of the active semester narrowed by the active filters, ordered like the list:
     * upcoming deadlines first (nearest on top), then past ones (most recent on top).
     */
    protected function tugasQuery(): Builder
    {
        $now = now()->toDateTimeString();

        return Tugas::query()
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->when($this->status === 'aktif', fn ($query) => $query->belumDeadline())
            ->when($this->status === 'lewat', fn ($query) => $query->lewatDeadline())
            ->search($this->search)
            ->orderByRaw('case when deadline >= ? then 0 else 1 end', [$now])
            ->orderByRaw('case when deadline >= ? then deadline else null end asc', [$now])
            ->orderByDesc('deadline');
    }

    #[Computed]
    public function daftarTugas(): LengthAwarePaginator
    {
        return $this->tugasQuery()
            ->select(['id', 'mata_kuliah_id', 'kategori_kelompok_id', 'nama', 'deskripsi', 'deadline', 'link_pengumpulan', 'created_at', 'updated_at'])
            ->with(['mataKuliah:id,nama,dosen', 'kategoriKelompok:id,nama'])
            ->withCount('lampiran')
            ->paginate($this->perPage());
    }

    /**
     * WhatsApp share message per tugas on the current page (tugas id => text).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function teksWhatsApp(): array
    {
        return $this->daftarTugas->getCollection()
            ->mapWithKeys(fn (Tugas $tugas) => [$tugas->id => PesanWhatsApp::tugas($tugas, $this->kelas)])
            ->all();
    }

    /**
     * Two lembar: the tugas list (with attachment count) and one row per attached file.
     */
    public function export(): StreamedResponse
    {
        $statusLabel = ['aktif' => 'Aktif', 'segera' => 'Segera', 'lewat' => 'Lewat'];

        $daftar = $this->tugasQuery()
            ->with(['mataKuliah:id,nama,dosen', 'kategoriKelompok:id,nama', 'creator:id,name', 'lampiran:id,tugas_id,nama,ukuran'])
            ->get();

        $baris = $daftar->map(fn (Tugas $tugas, int $indeks) => [
            $indeks + 1,
            $tugas->nama,
            $tugas->mataKuliah->nama,
            $tugas->mataKuliah->dosen,
            $tugas->deadline,
            $statusLabel[$tugas->status()],
            $tugas->kategoriKelompok?->nama,
            $tugas->link_pengumpulan,
            $tugas->lampiran->count(),
            $tugas->deskripsi,
            $tugas->creator?->name,
            $tugas->created_at,
        ]);

        $lampiran = $daftar
            ->flatMap(fn (Tugas $tugas) => $tugas->lampiran->map(fn (TugasLampiran $lampiran) => [
                $tugas->nama,
                $tugas->mataKuliah->nama,
                $lampiran->nama,
                $lampiran->ukuranTerbaca(),
            ]))
            ->values()
            ->map(fn (array $baris, int $indeks) => [$indeks + 1, ...$baris]);

        return ExcelExport::buat('Daftar Tugas')
            ->subjudul('Kelas '.$this->kelas->nama.' · '.$this->kelas->semesterAktif->nama)
            ->filter([
                'Pencarian' => $this->search,
                'Mata kuliah' => $this->mataKuliahId !== '' ? $this->mataKuliahOptions->firstWhere('id', (int) $this->mataKuliahId)?->nama : null,
                'Status' => ['aktif' => 'Belum deadline', 'lewat' => 'Lewat deadline'][$this->status] ?? null,
            ])
            ->lembar('Tugas')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Nama Tugas',
                'Mata Kuliah',
                'Dosen',
                ['Deadline', ExcelExport::TIPE_WAKTU],
                'Status',
                'Tugas Kelompok (Kategori)',
                'Link Pengumpulan',
                ['Jumlah Lampiran', ExcelExport::TIPE_ANGKA],
                ['Deskripsi', ExcelExport::TIPE_PANJANG],
                'Dibuat Oleh',
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->lembar('Lampiran')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Nama Tugas',
                'Mata Kuliah',
                'Nama File',
                'Ukuran',
            )
            ->baris($lampiran)
            ->unduh('daftar-tugas '.$this->kelas->nama);
    }

    protected function afterSave(Tugas $tugas): void
    {
        unset($this->daftarTugas, $this->teksWhatsApp);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarTugas, $this->teksWhatsApp);
    }

    public function render()
    {
        return view('livewire.tugas.index');
    }
}

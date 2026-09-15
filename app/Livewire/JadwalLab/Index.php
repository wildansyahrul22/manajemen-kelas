<?php

namespace App\Livewire\JadwalLab;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\JadwalLabForm;
use App\Models\JadwalLab;
use App\Models\MataKuliah;
use App\Support\ExcelExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Praktikum sessions of the active semester, one card per mata kuliah that has any. Pick a mata
 * kuliah (in the form, a card's "+", or the filter) and add several dated sessions to it at once.
 */
#[Title('Jadwal Lab')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies;

    public JadwalLabForm $form;

    #[Url(as: 'mk', except: '')]
    public string $mataKuliahId = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * Mata kuliah of the active semester that have at least one lab session matching the status
     * filter, each with those sessions ordered by tanggal. Mata kuliah without sessions are not listed.
     *
     * @return Collection<int, MataKuliah>
     */
    #[Computed]
    public function mataKuliahDenganJadwal(): Collection
    {
        return MataKuliah::query()
            ->select(['id', 'kode', 'nama', 'dosen'])
            ->whereHas('jadwalLab', fn (Builder $query) => $this->sesuaiStatus($query))
            ->with(['jadwalLab' => fn ($query) => $this->sesuaiStatus($query)
                ->select(['id', 'mata_kuliah_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'ruangan', 'keterangan'])
                ->urut()])
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->whereKey((int) $this->mataKuliahId))
            ->orderBy('nama')
            ->get();
    }

    /**
     * Apply the status filter (mendatang / lewat) to a JadwalLab query.
     */
    protected function sesuaiStatus(mixed $query): mixed
    {
        return $query
            ->when($this->status === 'mendatang', fn ($query) => $query->mendatang())
            ->when($this->status === 'lewat', fn ($query) => $query->lewat());
    }

    /**
     * Same sessions as the page (active filters applied), but flat and in chronological order.
     */
    public function export(): StreamedResponse
    {
        $baris = $this->sesuaiStatus(JadwalLab::query())
            ->with('mataKuliah:id,nama,dosen')
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->urut()
            ->get()
            ->map(fn (JadwalLab $jadwal, int $indeks) => [
                $indeks + 1,
                $jadwal->tanggal,
                $jadwal->tanggal->isoFormat('dddd'),
                $jadwal->jam_mulai,
                $jadwal->jam_selesai,
                $jadwal->mataKuliah->nama,
                $jadwal->mataKuliah->dosen,
                $jadwal->ruangan,
                $jadwal->keterangan,
                match (true) {
                    $jadwal->isLewat() => 'Lewat',
                    $jadwal->isHariIni() => 'Hari ini',
                    default => 'Mendatang',
                },
            ]);

        return ExcelExport::buat('Jadwal Lab')
            ->subjudul('Kelas '.$this->kelas->nama.' · '.$this->kelas->semesterAktif->nama)
            ->filter([
                'Mata kuliah' => $this->mataKuliahId !== '' ? $this->mataKuliahOptions->firstWhere('id', (int) $this->mataKuliahId)?->nama : null,
                'Status' => ['mendatang' => 'Mendatang', 'lewat' => 'Sudah lewat'][$this->status] ?? null,
            ])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                ['Tanggal', ExcelExport::TIPE_TANGGAL],
                'Hari',
                ['Jam Mulai', ExcelExport::TIPE_JAM],
                ['Jam Selesai', ExcelExport::TIPE_JAM],
                'Mata Kuliah',
                'Dosen',
                'Ruangan',
                ['Keterangan', ExcelExport::TIPE_PANJANG],
                'Status',
            )
            ->baris($baris)
            ->unduh('jadwal-lab '.$this->kelas->nama);
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    public function openCreate(?int $mataKuliahId = null): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->startCreate($mataKuliahId ?? ($this->mataKuliahId !== '' ? (int) $this->mataKuliahId : null));
        $this->openForm();
    }

    public function addSesi(): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->addSesi();
    }

    public function removeSesi(string $key): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->removeSesi($key);
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $jadwalLab = JadwalLab::query()->findOrFail($id);

        $this->authorize('update', $jadwalLab);

        $this->form->fillFrom($jadwalLab);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->jadwalLab !== null;

        $isEdit
            ? $this->authorize('update', $this->form->jadwalLab)
            : $this->authorize('create', JadwalLab::class);

        $saved = $this->form->save($this->kelas);

        $this->closeForm();
        unset($this->mataKuliahDenganJadwal);
        $this->notify(match (true) {
            $isEdit => 'Jadwal lab berhasil diperbarui.',
            $saved->count() > 1 => $saved->count().' jadwal lab berhasil ditambahkan.',
            default => 'Jadwal lab berhasil ditambahkan.',
        });
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', JadwalLab::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $jadwalLab = JadwalLab::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $jadwalLab);

        $jadwalLab->delete();

        $this->closeDelete();
        unset($this->mataKuliahDenganJadwal);
        $this->notify('Jadwal lab berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.jadwal-lab.index');
    }
}

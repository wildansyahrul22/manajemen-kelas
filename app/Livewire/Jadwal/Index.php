<?php

namespace App\Livewire\Jadwal;

use App\Enums\Hari;
use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\JadwalKelasForm;
use App\Models\JadwalKelas;
use App\Models\MataKuliah;
use App\Support\ExcelExport;
use App\Support\PesanWhatsApp;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Jadwal Kelas')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies;

    public JadwalKelasForm $form;

    /**
     * Schedule of the active semester grouped per day (Senin..Minggu).
     *
     * @return Collection<int, Collection<int, JadwalKelas>>
     */
    #[Computed]
    public function jadwalPerHari(): Collection
    {
        $jadwal = JadwalKelas::query()
            ->select(['id', 'mata_kuliah_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])
            ->with('mataKuliah:id,ulid,nama,dosen,kode')
            ->forKelasAktif($this->kelas)
            ->urut()
            ->get()
            ->groupBy(fn (JadwalKelas $jadwal) => $jadwal->hari->value);

        return collect(Hari::cases())
            ->mapWithKeys(fn (Hari $hari) => [$hari->value => $jadwal->get($hari->value, collect())]);
    }

    /**
     * WhatsApp share message per hari that has sessions (hari value => text).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function teksWhatsApp(): array
    {
        return $this->jadwalPerHari
            ->filter(fn (Collection $daftar) => $daftar->isNotEmpty())
            ->map(fn (Collection $daftar, int $hari) => PesanWhatsApp::jadwalKelas(Hari::from($hari), $daftar, $this->kelas))
            ->all();
    }

    /**
     * The whole week in one WhatsApp message; null while no session exists.
     */
    #[Computed]
    public function teksWhatsAppMingguan(): ?string
    {
        return $this->teksWhatsApp === [] ? null : PesanWhatsApp::jadwalKelasMingguan($this->jadwalPerHari, $this->kelas);
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    public function export(): StreamedResponse
    {
        $baris = JadwalKelas::query()
            ->with('mataKuliah:id,nama,kode,dosen')
            ->forKelasAktif($this->kelas)
            ->urut()
            ->get()
            ->map(fn (JadwalKelas $jadwal, int $indeks) => [
                $indeks + 1,
                $jadwal->hari->label(),
                $jadwal->jam_mulai,
                $jadwal->jam_selesai,
                $jadwal->mataKuliah->nama,
                $jadwal->mataKuliah->kode,
                $jadwal->mataKuliah->dosen,
                $jadwal->ruangan,
            ]);

        return ExcelExport::buat('Jadwal Kelas')
            ->subjudul('Kelas '.$this->kelas->nama.' · '.$this->kelas->semesterAktif->nama)
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Hari',
                ['Jam Mulai', ExcelExport::TIPE_JAM],
                ['Jam Selesai', ExcelExport::TIPE_JAM],
                'Mata Kuliah',
                'Kode',
                'Dosen',
                'Ruangan',
            )
            ->baris($baris)
            ->unduh('jadwal-kelas '.$this->kelas->nama);
    }

    public function openCreate(?int $hari = null): void
    {
        $this->authorize('create', JadwalKelas::class);

        $this->form->startCreate($hari);
        $this->openForm();
    }

    public function addSesi(): void
    {
        $this->authorize('create', JadwalKelas::class);

        $this->form->addSesi();
    }

    public function removeSesi(string $key): void
    {
        $this->authorize('create', JadwalKelas::class);

        $this->form->removeSesi($key);
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $jadwal = JadwalKelas::query()->findOrFail($id);

        $this->authorize('update', $jadwal);

        $this->form->fillFrom($jadwal);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->jadwal !== null;

        $isEdit
            ? $this->authorize('update', $this->form->jadwal)
            : $this->authorize('create', JadwalKelas::class);

        $saved = $this->form->save($this->kelas);

        $this->closeForm();
        unset($this->jadwalPerHari, $this->teksWhatsApp, $this->teksWhatsAppMingguan);
        $this->notify(match (true) {
            $isEdit => 'Jadwal berhasil diperbarui.',
            $saved->count() > 1 => $saved->count().' jadwal berhasil ditambahkan.',
            default => 'Jadwal berhasil ditambahkan.',
        });
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', JadwalKelas::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $jadwal = JadwalKelas::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $jadwal);

        $jadwal->delete();

        $this->closeDelete();
        unset($this->jadwalPerHari, $this->teksWhatsApp, $this->teksWhatsAppMingguan);
        $this->notify('Jadwal berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.jadwal.index', ['hariIni' => Hari::today()]);
    }
}

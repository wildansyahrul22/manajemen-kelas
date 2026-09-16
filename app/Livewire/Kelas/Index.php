<?php

namespace App\Livewire\Kelas;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KelasForm;
use App\Models\Kampus;
use App\Models\Kelas;
use App\Models\Semester;
use App\Support\ExcelExport;
use App\Support\KelasContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Kelas')]
class Index extends Component
{
    use ManagesModalForm, Notifies, WithTableControls;

    public KelasForm $form;

    #[Url(as: 'kampus', except: '')]
    public string $kampusId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Kelas::class);
    }

    public function updatedKampusId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function kampusOptions(): Collection
    {
        return Kampus::query()->orderBy('nama')->get(['id', 'nama']);
    }

    #[Computed]
    public function semesterOptions(): Collection
    {
        return Semester::query()->orderBy('nomor')->get(['id', 'nama']);
    }

    protected function kelasQuery(): Builder
    {
        return Kelas::query()
            ->select(['id', 'kampus_id', 'nama', 'prodi', 'angkatan', 'semester_aktif_id', 'masa_aktif_mulai', 'masa_aktif_selesai', 'upload', 'created_at'])
            ->with(['kampus:id,nama', 'semesterAktif:id,nama'])
            ->withCount(['mahasiswa' => fn ($query) => $query->aktifDiSemesterKelas(), 'mataKuliah'])
            ->when($this->kampusId !== '', fn ($query) => $query->where('kampus_id', (int) $this->kampusId))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama');
    }

    #[Computed]
    public function daftarKelas(): LengthAwarePaginator
    {
        return $this->kelasQuery()->paginate($this->perPage());
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Kelas::class);

        $baris = $this->kelasQuery()
            ->get()
            ->map(fn (Kelas $kelas, int $indeks) => [
                $indeks + 1,
                $kelas->nama,
                $kelas->kampus->nama,
                $kelas->prodi,
                $kelas->angkatan,
                $kelas->semesterAktif->nama,
                $kelas->masaAktifTerbaca() ?? 'Tanpa batas',
                $kelas->isAktif() ? 'Aktif' : ($kelas->sudahBerakhir() ? 'Kedaluwarsa' : 'Belum mulai'),
                $kelas->bolehUpload() ? 'Ya' : 'Tidak',
                $kelas->mahasiswa_count,
                $kelas->mata_kuliah_count,
                $kelas->created_at,
            ]);

        return ExcelExport::buat('Kelas')
            ->subjudul('Semua kelas')
            ->filter([
                'Kampus' => $this->kampusId !== '' ? $this->kampusOptions->firstWhere('id', (int) $this->kampusId)?->nama : null,
                'Pencarian' => $this->search,
            ])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Nama Kelas',
                'Kampus',
                'Prodi',
                ['Angkatan', ExcelExport::TIPE_ANGKA],
                'Semester Aktif',
                'Masa Aktif',
                'Status',
                'Fitur Upload',
                ['Jumlah Mahasiswa', ExcelExport::TIPE_ANGKA],
                ['Jumlah Mata Kuliah', ExcelExport::TIPE_ANGKA],
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->unduh('kelas');
    }

    public function openCreate(): void
    {
        $this->authorize('create', Kelas::class);

        $this->form->reset();
        $this->form->kampus_id = (string) $this->kampusOptions->first()?->id;
        $this->form->angkatan = (string) now()->year;
        $this->form->semester_aktif_id = (string) $this->semesterOptions->first()?->id;
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kelas = Kelas::query()->findOrFail($id);

        $this->authorize('update', $kelas);

        $this->form->fillFrom($kelas);
        $this->openForm();
    }

    public function save(KelasContext $context): void
    {
        $isEdit = $this->form->kelas !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kelas)
            : $this->authorize('create', Kelas::class);

        $kelas = $this->form->save();

        if (! $isEdit && $context->current() === null) {
            $context->switchTo($kelas);
        }

        $this->closeForm();
        unset($this->daftarKelas);
        $this->notify($isEdit ? 'Kelas berhasil diperbarui.' : 'Kelas berhasil ditambahkan.');

        if (! $isEdit) {
            $this->redirectRoute('kelas.index', navigate: true);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Kelas::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(KelasContext $context): void
    {
        $kelas = Kelas::query()->withCount('users')->findOrFail($this->deletingId);

        $this->authorize('delete', $kelas);

        if ($kelas->users_count > 0) {
            $this->closeDelete();
            $this->notify("Kelas masih memiliki {$kelas->users_count} user. Pindahkan atau hapus user-nya terlebih dahulu.", 'error');

            return;
        }

        $isKelasAktif = $context->id() === $kelas->id;

        $kelas->delete();

        $this->closeDelete();

        // The header filter pointed at this kelas: reload so the context picks another one.
        if ($isKelasAktif) {
            session()->forget(KelasContext::SESSION_KEY);
            $this->flashNotify('Kelas berhasil dihapus.');
            $this->redirectRoute('kelas.index', navigate: true);

            return;
        }

        unset($this->daftarKelas);
        $this->notify('Kelas berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kelas.index');
    }
}

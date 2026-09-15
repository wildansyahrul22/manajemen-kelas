<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\User;
use App\Support\ExcelExport;
use App\Support\PesanWhatsApp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Kelompok of the active semester narrowed by the active filters, ordered like the list.
     */
    protected function kelompokQuery(): Builder
    {
        return Kelompok::query()
            ->select(['id', 'mata_kuliah_id', 'kategori_kelompok_id', 'nama', 'deskripsi', 'created_by'])
            ->with(['mataKuliah:id,kelas_id,nama', 'kategori:id,nama'])
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->when($this->kategoriId !== '', fn ($query) => $query->where('kategori_kelompok_id', (int) $this->kategoriId))
            ->search($this->search)
            ->orderBy('mata_kuliah_id')
            ->orderBy('kategori_kelompok_id')
            ->orderBy('nama');
    }

    /**
     * WhatsApp share message for the kategori picked in the filter: every kelompok of that kategori
     * with its anggota, plus students of the kelas not placed yet. Null until a kategori is chosen.
     */
    #[Computed]
    public function teksWhatsApp(): ?string
    {
        if ($this->kategoriId === '') {
            return null;
        }

        $kategori = KategoriKelompok::query()
            ->with('mataKuliah:id,nama')
            ->forKelasAktif($this->kelas)
            ->find((int) $this->kategoriId);

        if ($kategori === null) {
            return null;
        }

        $daftar = Kelompok::query()
            ->with(['anggota' => fn ($query) => $query->select(['users.id', 'users.npm', 'users.name'])])
            ->where('kategori_kelompok_id', $kategori->id)
            ->orderBy('nama')
            ->get();

        $belumPunyaKelompok = User::query()
            ->forKelas($this->kelas->id)
            ->anggotaKelas()
            ->aktifDiSemester($this->kelas->semester_aktif_id)
            ->whereNotIn('id', KategoriKelompok::anggotaIds($kategori->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return PesanWhatsApp::kelompok($kategori, $daftar, $belumPunyaKelompok, $this->kelas);
    }

    #[Computed]
    public function daftarKelompok(): LengthAwarePaginator
    {
        return $this->kelompokQuery()
            ->with(['anggota' => fn ($query) => $query->select(['users.id', 'users.name'])])
            ->paginate($this->perPage());
    }

    /**
     * Two lembar: one row per anggota (NPM + nama + peran) and one row per kelompok
     * (ketua and member count). Names are never joined into a single cell.
     */
    public function export(): StreamedResponse
    {
        $daftar = $this->kelompokQuery()
            ->with(['anggota' => fn ($query) => $query->select(['users.id', 'users.npm', 'users.name'])])
            ->get();

        $ringkasan = $daftar->map(function (Kelompok $kelompok, int $indeks) {
            $ketua = $kelompok->anggota->first(fn (User $anggota) => (bool) $anggota->pivot->is_ketua);

            return [
                $indeks + 1,
                $kelompok->mataKuliah->nama,
                $kelompok->kategori->nama,
                $kelompok->nama,
                $ketua?->npm,
                $ketua?->name,
                $kelompok->anggota->count(),
                $kelompok->deskripsi,
            ];
        });

        $anggota = $daftar
            ->flatMap(fn (Kelompok $kelompok) => $kelompok->anggota->map(fn (User $anggota) => [
                $kelompok->mataKuliah->nama,
                $kelompok->kategori->nama,
                $kelompok->nama,
                $anggota->npm,
                $anggota->name,
                $anggota->pivot->is_ketua ? 'Ketua' : 'Anggota',
            ]))
            ->values()
            ->map(fn (array $baris, int $indeks) => [$indeks + 1, ...$baris]);

        return ExcelExport::buat('Kelompok')
            ->subjudul('Kelas '.$this->kelas->nama.' · '.$this->kelas->semesterAktif->nama)
            ->filter([
                'Pencarian' => $this->search,
                'Mata kuliah' => $this->mataKuliahId !== '' ? $this->mataKuliahOptions->firstWhere('id', (int) $this->mataKuliahId)?->nama : null,
                'Kategori' => $this->kategoriId !== '' ? $this->kategoriFilterOptions->get((int) $this->kategoriId) : null,
            ])
            ->lembar('Anggota')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Mata Kuliah',
                'Kategori',
                'Kelompok',
                'NPM',
                'Nama',
                'Peran',
            )
            ->baris($anggota)
            ->lembar('Kelompok')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Mata Kuliah',
                'Kategori',
                'Nama Kelompok',
                'NPM Ketua',
                'Nama Ketua',
                ['Jumlah Anggota', ExcelExport::TIPE_ANGKA],
                ['Deskripsi', ExcelExport::TIPE_PANJANG],
            )
            ->baris($ringkasan)
            ->unduh('kelompok '.$this->kelas->nama);
    }

    protected function afterSave(Kelompok $kelompok): void
    {
        unset($this->daftarKelompok, $this->kategoriFilterOptions, $this->teksWhatsApp);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarKelompok, $this->teksWhatsApp);
    }

    public function render()
    {
        return view('livewire.kelompok.index');
    }
}

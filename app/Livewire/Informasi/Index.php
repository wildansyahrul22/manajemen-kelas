<?php

namespace App\Livewire\Informasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Informasi;
use App\Models\InformasiLampiran;
use App\Support\ExcelExport;
use App\Support\PesanWhatsApp;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Daftar Informasi')]
class Index extends Component
{
    use InteractsWithKelas, ManagesInformasi, Notifies, WithTableControls;

    #[Url(as: 'kategori', except: '')]
    public string $kategoriId = '';

    public function updatedKategoriId(): void
    {
        $this->resetPage();
    }

    /**
     * Informasi of this kelas narrowed by the active filters, pinned first then newest.
     */
    protected function informasiQuery(): Builder
    {
        return Informasi::query()
            ->select(['id', 'ulid', 'kelas_id', 'kategori_informasi_id', 'judul', 'isi', 'link', 'is_pinned', 'created_by', 'created_at'])
            ->with(['kategori:id,nama,warna', 'creator:id,name'])
            ->withCount('lampiran')
            ->forKelas($this->kelas->id)
            ->when($this->kategoriId !== '', fn ($query) => $query->where('kategori_informasi_id', (int) $this->kategoriId))
            ->search($this->search)
            ->terbaru();
    }

    #[Computed]
    public function daftarInformasi(): LengthAwarePaginator
    {
        return $this->informasiQuery()->paginate($this->perPage());
    }

    /**
     * WhatsApp share message per informasi on the current page (informasi id => text).
     *
     * @return array<int, string>
     */
    #[Computed]
    public function teksWhatsApp(): array
    {
        return $this->daftarInformasi->getCollection()
            ->mapWithKeys(fn (Informasi $informasi) => [$informasi->id => PesanWhatsApp::informasi($informasi, $this->kelas)])
            ->all();
    }

    /**
     * Two lembar: the informasi list (with attachment count) and one row per attached file.
     */
    public function export(): StreamedResponse
    {
        $daftar = $this->informasiQuery()->with('lampiran:id,informasi_id,nama,ukuran')->get();

        $baris = $daftar->map(fn (Informasi $informasi, int $indeks) => [
            $indeks + 1,
            $informasi->judul,
            $informasi->kategori->nama,
            $informasi->isi,
            $informasi->link,
            $informasi->lampiran->count(),
            $informasi->is_pinned,
            $informasi->creator?->name,
            $informasi->created_at,
        ]);

        $lampiran = $daftar
            ->flatMap(fn (Informasi $informasi) => $informasi->lampiran->map(fn (InformasiLampiran $lampiran) => [
                $informasi->judul,
                $informasi->kategori->nama,
                $lampiran->nama,
                $lampiran->ukuranTerbaca(),
            ]))
            ->values()
            ->map(fn (array $baris, int $indeks) => [$indeks + 1, ...$baris]);

        return ExcelExport::buat('Daftar Informasi')
            ->subjudul('Kelas '.$this->kelas->nama)
            ->filter([
                'Pencarian' => $this->search,
                'Kategori' => $this->kategoriId !== '' ? $this->kategoriOptions->firstWhere('id', (int) $this->kategoriId)?->nama : null,
            ])
            ->lembar('Informasi')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Judul',
                'Kategori',
                ['Isi', ExcelExport::TIPE_PANJANG],
                'Tautan',
                ['Jumlah Lampiran', ExcelExport::TIPE_ANGKA],
                'Disematkan',
                'Dibuat Oleh',
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->lembar('Lampiran')
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Judul Informasi',
                'Kategori',
                'Nama File',
                'Ukuran',
            )
            ->baris($lampiran)
            ->unduh('daftar-informasi '.$this->kelas->nama);
    }

    protected function afterSave(Informasi $informasi): void
    {
        unset($this->daftarInformasi, $this->teksWhatsApp);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarInformasi, $this->teksWhatsApp);
    }

    public function render()
    {
        return view('livewire.informasi.index');
    }
}

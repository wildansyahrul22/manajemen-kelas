<?php

namespace App\Livewire\Informasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Informasi;
use App\Support\ExcelExport;
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
            ->select(['id', 'kelas_id', 'kategori_informasi_id', 'judul', 'isi', 'link', 'lampiran_path', 'lampiran_nama', 'is_pinned', 'created_by', 'created_at'])
            ->with(['kategori:id,nama,warna', 'creator:id,name'])
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

    public function export(): StreamedResponse
    {
        $baris = $this->informasiQuery()
            ->get()
            ->map(fn (Informasi $informasi, int $indeks) => [
                $indeks + 1,
                $informasi->judul,
                $informasi->kategori->nama,
                $informasi->isi,
                $informasi->link,
                $informasi->lampiran_nama,
                $informasi->is_pinned,
                $informasi->creator?->name,
                $informasi->created_at,
            ]);

        return ExcelExport::buat('Daftar Informasi')
            ->subjudul('Kelas '.$this->kelas->nama)
            ->filter([
                'Pencarian' => $this->search,
                'Kategori' => $this->kategoriId !== '' ? $this->kategoriOptions->firstWhere('id', (int) $this->kategoriId)?->nama : null,
            ])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Judul',
                'Kategori',
                ['Isi', ExcelExport::TIPE_PANJANG],
                'Tautan',
                'Lampiran',
                'Disematkan',
                'Dibuat Oleh',
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->unduh('daftar-informasi '.$this->kelas->nama);
    }

    protected function afterSave(Informasi $informasi): void
    {
        unset($this->daftarInformasi);
    }

    protected function afterDelete(): void
    {
        unset($this->daftarInformasi);
    }

    public function render()
    {
        return view('livewire.informasi.index');
    }
}

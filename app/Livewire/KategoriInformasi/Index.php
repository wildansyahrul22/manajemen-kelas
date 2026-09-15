<?php

namespace App\Livewire\KategoriInformasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KategoriInformasiForm;
use App\Models\KategoriInformasi;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Kategori Informasi')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies, WithTableControls;

    public KategoriInformasiForm $form;

    /**
     * Kategori of this kelas narrowed by the search box, with their informasi count.
     */
    protected function kategoriQuery(): Builder
    {
        return KategoriInformasi::query()
            ->select(['id', 'kelas_id', 'nama', 'warna', 'created_by', 'created_at'])
            ->withCount('informasi')
            ->where('kelas_id', $this->kelas->id)
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama');
    }

    #[Computed]
    public function daftarKategori(): LengthAwarePaginator
    {
        return $this->kategoriQuery()->paginate($this->perPage());
    }

    public function export(): StreamedResponse
    {
        $baris = $this->kategoriQuery()
            ->with('creator:id,name')
            ->get()
            ->map(fn (KategoriInformasi $kategori, int $indeks) => [
                $indeks + 1,
                $kategori->nama,
                ucfirst($kategori->warna),
                $kategori->informasi_count,
                $kategori->creator?->name,
                $kategori->created_at,
            ]);

        return ExcelExport::buat('Kategori Informasi')
            ->subjudul('Kelas '.$this->kelas->nama)
            ->filter(['Pencarian' => $this->search])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Nama Kategori',
                'Warna',
                ['Jumlah Informasi', ExcelExport::TIPE_ANGKA],
                'Dibuat Oleh',
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->unduh('kategori-informasi '.$this->kelas->nama);
    }

    public function openCreate(): void
    {
        $this->authorize('create', KategoriInformasi::class);

        $this->form->reset();
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kategori = KategoriInformasi::query()->findOrFail($id);

        $this->authorize('update', $kategori);

        $this->form->fillFrom($kategori);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->kategori !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kategori)
            : $this->authorize('create', KategoriInformasi::class);

        $this->form->save($this->kelas, auth()->user());

        $this->closeForm();
        unset($this->daftarKategori);
        $this->notify($isEdit ? 'Kategori berhasil diperbarui.' : 'Kategori berhasil ditambahkan.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', KategoriInformasi::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $kategori = KategoriInformasi::query()->withCount('informasi')->findOrFail($this->deletingId);

        $this->authorize('delete', $kategori);

        if ($kategori->informasi_count > 0) {
            $this->closeDelete();
            $this->notify("Kategori masih dipakai oleh {$kategori->informasi_count} informasi. Pindahkan atau hapus informasinya dulu.", 'error');

            return;
        }

        $kategori->delete();

        $this->closeDelete();
        unset($this->daftarKategori);
        $this->notify('Kategori berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kategori-informasi.index', ['warnaOptions' => KategoriInformasi::WARNA]);
    }
}

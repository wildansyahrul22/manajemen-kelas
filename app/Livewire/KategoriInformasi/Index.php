<?php

namespace App\Livewire\KategoriInformasi;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KategoriInformasiForm;
use App\Models\KategoriInformasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Kategori Informasi')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies, WithTableControls;

    public KategoriInformasiForm $form;

    #[Computed]
    public function daftarKategori(): LengthAwarePaginator
    {
        return KategoriInformasi::query()
            ->select(['id', 'kelas_id', 'nama', 'warna', 'created_at'])
            ->withCount('informasi')
            ->where('kelas_id', $this->kelas->id)
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama')
            ->paginate($this->perPage());
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

        $this->form->save($this->kelas);

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

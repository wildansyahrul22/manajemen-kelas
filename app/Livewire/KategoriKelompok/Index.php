<?php

namespace App\Livewire\KategoriKelompok;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KategoriKelompokForm;
use App\Models\KategoriKelompok;
use App\Models\MataKuliah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Kategori Kelompok')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies, WithTableControls;

    public KategoriKelompokForm $form;

    #[Url(as: 'mk', except: '')]
    public string $mataKuliahId = '';

    public function updatedMataKuliahId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function daftarKategori(): LengthAwarePaginator
    {
        return KategoriKelompok::query()
            ->select(['id', 'mata_kuliah_id', 'nama', 'created_by', 'created_at'])
            ->with('mataKuliah:id,kelas_id,nama')
            ->withCount('kelompok')
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->where('mata_kuliah_id', (int) $this->mataKuliahId))
            ->search($this->search)
            ->orderBy('mata_kuliah_id')
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    public function openCreate(): void
    {
        $this->authorize('create', KategoriKelompok::class);

        $this->form->reset();
        $this->form->mata_kuliah_id = $this->mataKuliahId;
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kategori = KategoriKelompok::query()->findOrFail($id);

        $this->authorize('update', $kategori);

        $this->form->fillFrom($kategori);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->kategori !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kategori)
            : $this->authorize('create', KategoriKelompok::class);

        $this->form->save($this->kelas, auth()->user());

        $this->closeForm();
        unset($this->daftarKategori);
        $this->notify($isEdit ? 'Kategori berhasil diperbarui.' : 'Kategori berhasil ditambahkan.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', KategoriKelompok::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $kategori = KategoriKelompok::query()->withCount('kelompok')->findOrFail($this->deletingId);

        $this->authorize('delete', $kategori);

        if ($kategori->kelompok_count > 0) {
            $this->closeDelete();
            $this->notify("Kategori masih dipakai oleh {$kategori->kelompok_count} kelompok. Hapus atau pindahkan kelompoknya dulu.", 'error');

            return;
        }

        $kategori->delete();

        $this->closeDelete();
        unset($this->daftarKategori);
        $this->notify('Kategori berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kategori-kelompok.index');
    }
}

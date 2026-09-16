<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\KelompokForm;
use App\Models\KategoriKelompok;
use App\Models\Kelompok;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait ManagesKelompok
{
    use ManagesModalForm;

    public KelompokForm $form;

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Kategori of the active semester, labelled "Kategori · Mata kuliah" (id => label).
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function kategoriOptions(): Collection
    {
        return KategoriKelompok::query()
            ->select(['id', 'mata_kuliah_id', 'nama', 'final'])
            ->with('mataKuliah:id,nama')
            ->forKelasAktif($this->kelas)
            ->get()
            ->sortBy([['mataKuliah.nama', 'asc'], ['nama', 'asc']])
            ->mapWithKeys(fn (KategoriKelompok $kategori) => [$kategori->id => $kategori->nama.' · '.$kategori->mataKuliah->nama]);
    }

    /**
     * Students still free for the kategori picked in the form: those already placed in another
     * kelompok of that kategori are left out (members of the kelompok being edited stay).
     */
    #[Computed]
    public function mahasiswaOptions(): Collection
    {
        $kategoriId = (int) $this->form->kategori_kelompok_id;

        if ($kategoriId === 0) {
            return collect();
        }

        return User::query()
            ->forKelas($this->kelas->id)
            ->anggotaKelas()
            ->aktifDiSemester($this->kelas->semester_aktif_id)
            ->whereNotIn('id', KategoriKelompok::anggotaIds($kategoriId, $this->form->kelompok?->id))
            ->orderBy('name')
            ->get(['id', 'npm', 'name']);
    }

    public function openCreate(?int $kategoriId = null): void
    {
        $this->authorize('create', Kelompok::class);

        if ($kategoriId !== null) {
            $this->authorize('kelolaKelompok', KategoriKelompok::query()->findOrFail($kategoriId));
        }

        $this->form->reset();
        $this->form->kategori_kelompok_id = (string) ($kategoriId ?? '');
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kelompok = Kelompok::query()->findOrFail($id);

        $this->authorize('update', $kelompok);

        $this->form->fillFrom($kelompok);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->kelompok !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kelompok)
            : $this->authorize('create', Kelompok::class);

        $kelompok = $this->form->save($this->kelas, auth()->user());

        $this->closeForm();
        $this->notify($isEdit ? 'Kelompok berhasil diperbarui.' : 'Kelompok berhasil dibuat.');
        $this->afterSave($kelompok);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Kelompok::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $kelompok = Kelompok::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $kelompok);

        $kelompok->delete();

        $this->closeDelete();
        $this->notify('Kelompok berhasil dihapus.');
        $this->afterDelete();
    }

    protected function afterSave(Kelompok $kelompok): void {}

    protected function afterDelete(): void {}
}

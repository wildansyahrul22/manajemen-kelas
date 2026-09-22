<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\TugasForm;
use App\Models\KategoriKelompok;
use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

/**
 * Create / edit / delete behaviour shared by the tugas list and detail pages.
 */
trait ManagesTugas
{
    use ManagesModalForm, WithFileUploads;

    public TugasForm $form;

    /**
     * Whether the current user may attach files: admin kelas and super admin, and only when the
     * kelas' plan includes uploading.
     */
    #[Computed]
    public function canUpload(): bool
    {
        return auth()->user()->can('upload', [Tugas::class, $this->kelas]);
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    /**
     * Kategori kelompok of the mata kuliah picked in the form (id => nama); empty until one is picked.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function kategoriKelompokOptions(): Collection
    {
        if ($this->form->mata_kuliah_id === '') {
            return collect();
        }

        return KategoriKelompok::query()
            ->where('mata_kuliah_id', (int) $this->form->mata_kuliah_id)
            ->forKelasAktif($this->kelas)
            ->orderBy('nama')
            ->pluck('nama', 'id');
    }

    public function openCreate(): void
    {
        $this->authorize('create', Tugas::class);

        $this->form->reset();
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $tugas = Tugas::query()->findOrFail($id);

        $this->authorize('update', $tugas);

        $this->form->fillFrom($tugas);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->tugas !== null;

        $isEdit
            ? $this->authorize('update', $this->form->tugas)
            : $this->authorize('create', Tugas::class);

        $tugas = $this->form->save($this->kelas, auth()->user(), $this->canUpload);

        $this->closeForm();
        $this->notify($isEdit ? 'Tugas berhasil diperbarui.' : 'Tugas berhasil ditambahkan.');
        $this->afterSave($tugas);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Tugas::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $tugas = Tugas::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $tugas);

        $tugas->delete();

        // The modal template reads the attachments off form.tugas, so never keep a deleted model there.
        $this->form->reset();

        $this->closeDelete();
        $this->notify('Tugas berhasil dihapus.');
        $this->afterDelete();
    }

    protected function afterSave(Tugas $tugas): void {}

    protected function afterDelete(): void {}
}

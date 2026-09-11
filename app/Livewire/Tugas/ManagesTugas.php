<?php

namespace App\Livewire\Tugas;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\TugasForm;
use App\Models\MataKuliah;
use App\Models\Tugas;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

/**
 * Create / edit / delete behaviour shared by the tugas list and detail pages.
 */
trait ManagesTugas
{
    use ManagesModalForm;

    public TugasForm $form;

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
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

        $tugas = $this->form->save($this->kelas, auth()->user());

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

        $this->closeDelete();
        $this->notify('Tugas berhasil dihapus.');
        $this->afterDelete();
    }

    protected function afterSave(Tugas $tugas): void {}

    protected function afterDelete(): void {}
}

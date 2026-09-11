<?php

namespace App\Livewire\MataKuliah;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\MataKuliahForm;
use App\Models\MataKuliah;
use App\Models\Semester;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait ManagesMataKuliah
{
    use ManagesModalForm;

    public MataKuliahForm $form;

    #[Computed]
    public function semesterOptions(): Collection
    {
        return Semester::query()->orderBy('nomor')->get(['id', 'nomor', 'nama']);
    }

    public function openCreate(): void
    {
        $this->authorize('create', MataKuliah::class);

        $this->form->reset();
        $this->form->semester_id = (string) $this->kelas->semester_aktif_id;
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $mataKuliah = MataKuliah::query()->findOrFail($id);

        $this->authorize('update', $mataKuliah);

        $this->form->fillFrom($mataKuliah);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->mataKuliah !== null;

        $isEdit
            ? $this->authorize('update', $this->form->mataKuliah)
            : $this->authorize('create', MataKuliah::class);

        $mataKuliah = $this->form->save($this->kelas);

        $this->closeForm();
        $this->notify($isEdit ? 'Mata kuliah berhasil diperbarui.' : 'Mata kuliah berhasil ditambahkan.');
        $this->afterSave($mataKuliah);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', MataKuliah::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $mataKuliah = MataKuliah::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $mataKuliah);

        $mataKuliah->delete();

        $this->closeDelete();
        $this->notify('Mata kuliah beserta jadwal, tugas, dan kelompoknya berhasil dihapus.');
        $this->afterDelete();
    }

    protected function afterSave(MataKuliah $mataKuliah): void {}

    protected function afterDelete(): void {}
}

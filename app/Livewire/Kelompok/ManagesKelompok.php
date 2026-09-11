<?php

namespace App\Livewire\Kelompok;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\KelompokForm;
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
     * Students selectable as members (loaded once per request, filtered client-side).
     */
    #[Computed]
    public function mahasiswaOptions(): Collection
    {
        return User::query()
            ->forKelas($this->kelas->id)
            ->anggotaKelas()
            ->orderBy('name')
            ->get(['id', 'npm', 'name']);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Kelompok::class);

        $this->form->reset();
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

<?php

namespace App\Livewire\Informasi;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Forms\InformasiForm;
use App\Models\Informasi;
use App\Models\KategoriInformasi;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

trait ManagesInformasi
{
    use ManagesModalForm, WithFileUploads;

    public InformasiForm $form;

    /**
     * Whether the current user may attach files (super admin only); others share links.
     */
    #[Computed]
    public function canUpload(): bool
    {
        return auth()->user()->can('upload', Informasi::class);
    }

    #[Computed]
    public function kategoriOptions(): Collection
    {
        return KategoriInformasi::query()->where('kelas_id', $this->kelas->id)->orderBy('nama')->get(['id', 'nama', 'warna']);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Informasi::class);

        $this->form->reset();
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $informasi = Informasi::query()->findOrFail($id);

        $this->authorize('update', $informasi);

        $this->form->fillFrom($informasi);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->informasi !== null;

        $isEdit
            ? $this->authorize('update', $this->form->informasi)
            : $this->authorize('create', Informasi::class);

        $informasi = $this->form->save($this->kelas, auth()->user(), $this->canManage, $this->canUpload);

        $this->closeForm();
        $this->notify($isEdit ? 'Informasi berhasil diperbarui.' : 'Informasi berhasil dibagikan.');
        $this->afterSave($informasi);
    }

    public function togglePin(int $id): void
    {
        $informasi = Informasi::query()->findOrFail($id);

        $this->authorize('pin', $informasi);

        $informasi->update(['is_pinned' => ! $informasi->is_pinned]);

        $this->notify($informasi->is_pinned ? 'Informasi disematkan.' : 'Sematan dilepas.');
        $this->afterSave($informasi);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Informasi::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $informasi = Informasi::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $informasi);

        $informasi->delete();

        // The modal template reads the attached file off form.informasi, so never keep a deleted model there.
        $this->form->reset();

        $this->closeDelete();
        $this->notify('Informasi berhasil dihapus.');
        $this->afterDelete();
    }

    protected function afterSave(Informasi $informasi): void {}

    protected function afterDelete(): void {}
}

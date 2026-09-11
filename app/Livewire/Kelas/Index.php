<?php

namespace App\Livewire\Kelas;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KelasForm;
use App\Models\Kelas;
use App\Models\Semester;
use App\Support\KelasContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Kelas')]
class Index extends Component
{
    use ManagesModalForm, Notifies, WithTableControls;

    public KelasForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Kelas::class);
    }

    #[Computed]
    public function semesterOptions(): Collection
    {
        return Semester::query()->orderBy('nomor')->get(['id', 'nama']);
    }

    #[Computed]
    public function daftarKelas(): LengthAwarePaginator
    {
        return Kelas::query()
            ->select(['id', 'nama', 'prodi', 'angkatan', 'semester_aktif_id', 'created_at'])
            ->with('semesterAktif:id,nama')
            ->withCount(['mahasiswa', 'mataKuliah'])
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    public function openCreate(): void
    {
        $this->authorize('create', Kelas::class);

        $this->form->reset();
        $this->form->angkatan = (string) now()->year;
        $this->form->semester_aktif_id = (string) $this->semesterOptions->first()?->id;
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kelas = Kelas::query()->findOrFail($id);

        $this->authorize('update', $kelas);

        $this->form->fillFrom($kelas);
        $this->openForm();
    }

    public function save(KelasContext $context): void
    {
        $isEdit = $this->form->kelas !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kelas)
            : $this->authorize('create', Kelas::class);

        $kelas = $this->form->save();

        if (! $isEdit && $context->current() === null) {
            $context->switchTo($kelas);
        }

        $this->closeForm();
        unset($this->daftarKelas);
        $this->notify($isEdit ? 'Kelas berhasil diperbarui.' : 'Kelas berhasil ditambahkan.');

        if (! $isEdit) {
            $this->redirectRoute('kelas.index', navigate: true);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Kelas::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(KelasContext $context): void
    {
        $kelas = Kelas::query()->withCount('users')->findOrFail($this->deletingId);

        $this->authorize('delete', $kelas);

        if ($kelas->users_count > 0) {
            $this->closeDelete();
            $this->notify("Kelas masih memiliki {$kelas->users_count} user. Pindahkan atau hapus user-nya terlebih dahulu.", 'error');

            return;
        }

        $isKelasAktif = $context->id() === $kelas->id;

        $kelas->delete();

        $this->closeDelete();

        // The header filter pointed at this kelas: reload so the context picks another one.
        if ($isKelasAktif) {
            session()->forget(KelasContext::SESSION_KEY);
            $this->flashNotify('Kelas berhasil dihapus.');
            $this->redirectRoute('kelas.index', navigate: true);

            return;
        }

        unset($this->daftarKelas);
        $this->notify('Kelas berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kelas.index');
    }
}

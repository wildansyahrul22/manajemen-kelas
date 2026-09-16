<?php

namespace App\Livewire\Kampus;

use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\KampusForm;
use App\Models\Kampus;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Kampus')]
class Index extends Component
{
    use ManagesModalForm, Notifies, WithTableControls;

    public KampusForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Kampus::class);
    }

    protected function kampusQuery(): Builder
    {
        return Kampus::query()
            ->select(['id', 'nama', 'created_at'])
            ->withCount('kelas')
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama');
    }

    #[Computed]
    public function daftarKampus(): LengthAwarePaginator
    {
        return $this->kampusQuery()->paginate($this->perPage());
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Kampus::class);

        $baris = $this->kampusQuery()
            ->get()
            ->map(fn (Kampus $kampus, int $indeks) => [
                $indeks + 1,
                $kampus->nama,
                $kampus->kelas_count,
                $kampus->created_at,
            ]);

        return ExcelExport::buat('Kampus')
            ->subjudul('Semua kampus')
            ->filter(['Pencarian' => $this->search])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'Nama Kampus',
                ['Jumlah Kelas', ExcelExport::TIPE_ANGKA],
                ['Dibuat Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->unduh('kampus');
    }

    public function openCreate(): void
    {
        $this->authorize('create', Kampus::class);

        $this->form->reset();
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $kampus = Kampus::query()->findOrFail($id);

        $this->authorize('update', $kampus);

        $this->form->fillFrom($kampus);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->kampus !== null;

        $isEdit
            ? $this->authorize('update', $this->form->kampus)
            : $this->authorize('create', Kampus::class);

        $this->form->save();

        $this->closeForm();
        unset($this->daftarKampus);
        $this->notify($isEdit ? 'Kampus berhasil diperbarui.' : 'Kampus berhasil ditambahkan.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Kampus::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $kampus = Kampus::query()->withCount('kelas')->findOrFail($this->deletingId);

        $this->authorize('delete', $kampus);

        if ($kampus->kelas_count > 0) {
            $this->closeDelete();
            $this->notify("Kampus masih memiliki {$kampus->kelas_count} kelas. Pindahkan atau hapus kelasnya terlebih dahulu.", 'error');

            return;
        }

        $kampus->delete();

        $this->closeDelete();
        unset($this->daftarKampus);
        $this->notify('Kampus berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.kampus.index');
    }
}

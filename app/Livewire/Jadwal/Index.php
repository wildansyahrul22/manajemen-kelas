<?php

namespace App\Livewire\Jadwal;

use App\Enums\Hari;
use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\JadwalKelasForm;
use App\Models\JadwalKelas;
use App\Models\MataKuliah;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Jadwal Kelas')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies;

    public JadwalKelasForm $form;

    /**
     * Schedule of the active semester grouped per day (Senin..Minggu).
     *
     * @return Collection<int, Collection<int, JadwalKelas>>
     */
    #[Computed]
    public function jadwalPerHari(): Collection
    {
        $jadwal = JadwalKelas::query()
            ->select(['id', 'mata_kuliah_id', 'hari', 'jam_mulai', 'jam_selesai', 'ruangan'])
            ->with('mataKuliah:id,nama,dosen,kode')
            ->forKelasAktif($this->kelas)
            ->urut()
            ->get()
            ->groupBy(fn (JadwalKelas $jadwal) => $jadwal->hari->value);

        return collect(Hari::cases())
            ->mapWithKeys(fn (Hari $hari) => [$hari->value => $jadwal->get($hari->value, collect())]);
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    public function openCreate(?int $hari = null): void
    {
        $this->authorize('create', JadwalKelas::class);

        $this->form->reset();
        $this->form->hari = (string) ($hari ?? '');
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $jadwal = JadwalKelas::query()->findOrFail($id);

        $this->authorize('update', $jadwal);

        $this->form->fillFrom($jadwal);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->jadwal !== null;

        $isEdit
            ? $this->authorize('update', $this->form->jadwal)
            : $this->authorize('create', JadwalKelas::class);

        $this->form->save($this->kelas);

        $this->closeForm();
        unset($this->jadwalPerHari);
        $this->notify($isEdit ? 'Jadwal berhasil diperbarui.' : 'Jadwal berhasil ditambahkan.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', JadwalKelas::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $jadwal = JadwalKelas::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $jadwal);

        $jadwal->delete();

        $this->closeDelete();
        unset($this->jadwalPerHari);
        $this->notify('Jadwal berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.jadwal.index', ['hariIni' => Hari::today()]);
    }
}

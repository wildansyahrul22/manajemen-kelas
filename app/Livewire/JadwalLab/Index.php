<?php

namespace App\Livewire\JadwalLab;

use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\JadwalLabForm;
use App\Models\JadwalLab;
use App\Models\MataKuliah;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Praktikum sessions of the active semester, one card per mata kuliah that has any. Pick a mata
 * kuliah (in the form, a card's "+", or the filter) and add several dated sessions to it at once.
 */
#[Title('Jadwal Lab')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies;

    public JadwalLabForm $form;

    #[Url(as: 'mk', except: '')]
    public string $mataKuliahId = '';

    #[Url(except: '')]
    public string $status = '';

    /**
     * Mata kuliah of the active semester that have at least one lab session matching the status
     * filter, each with those sessions ordered by tanggal. Mata kuliah without sessions are not listed.
     *
     * @return Collection<int, MataKuliah>
     */
    #[Computed]
    public function mataKuliahDenganJadwal(): Collection
    {
        $sesiSesuaiStatus = fn ($query) => $query
            ->when($this->status === 'mendatang', fn ($query) => $query->mendatang())
            ->when($this->status === 'lewat', fn ($query) => $query->lewat());

        return MataKuliah::query()
            ->select(['id', 'kode', 'nama', 'dosen'])
            ->whereHas('jadwalLab', $sesiSesuaiStatus)
            ->with(['jadwalLab' => fn ($query) => $sesiSesuaiStatus($query)
                ->select(['id', 'mata_kuliah_id', 'tanggal', 'jam_mulai', 'jam_selesai', 'ruangan', 'keterangan'])
                ->urut()])
            ->forKelasAktif($this->kelas)
            ->when($this->mataKuliahId !== '', fn ($query) => $query->whereKey((int) $this->mataKuliahId))
            ->orderBy('nama')
            ->get();
    }

    #[Computed]
    public function mataKuliahOptions(): Collection
    {
        return MataKuliah::query()->forKelasAktif($this->kelas)->orderBy('nama')->get(['id', 'nama']);
    }

    public function openCreate(?int $mataKuliahId = null): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->startCreate($mataKuliahId ?? ($this->mataKuliahId !== '' ? (int) $this->mataKuliahId : null));
        $this->openForm();
    }

    public function addSesi(): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->addSesi();
    }

    public function removeSesi(string $key): void
    {
        $this->authorize('create', JadwalLab::class);

        $this->form->removeSesi($key);
        $this->resetValidation();
    }

    public function openEdit(int $id): void
    {
        $jadwalLab = JadwalLab::query()->findOrFail($id);

        $this->authorize('update', $jadwalLab);

        $this->form->fillFrom($jadwalLab);
        $this->openForm();
    }

    public function save(): void
    {
        $isEdit = $this->form->jadwalLab !== null;

        $isEdit
            ? $this->authorize('update', $this->form->jadwalLab)
            : $this->authorize('create', JadwalLab::class);

        $saved = $this->form->save($this->kelas);

        $this->closeForm();
        unset($this->mataKuliahDenganJadwal);
        $this->notify(match (true) {
            $isEdit => 'Jadwal lab berhasil diperbarui.',
            $saved->count() > 1 => $saved->count().' jadwal lab berhasil ditambahkan.',
            default => 'Jadwal lab berhasil ditambahkan.',
        });
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', JadwalLab::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $jadwalLab = JadwalLab::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $jadwalLab);

        $jadwalLab->delete();

        $this->closeDelete();
        unset($this->mataKuliahDenganJadwal);
        $this->notify('Jadwal lab berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.jadwal-lab.index');
    }
}

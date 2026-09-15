<?php

namespace App\Livewire\SemesterAktif;

use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\Kelas;
use App\Models\Semester;
use App\Support\KelasContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admin kelas: manage the active semester of their own kelas.
 * Super admin: manage the active semester of every kelas.
 */
#[Title('Semester Aktif')]
class Index extends Component
{
    use Notifies, WithTableControls;

    /** @var array<int, string> kelas id => chosen semester id */
    public array $pilihan = [];

    /**
     * Seed every kelas' combobox with its current semester. The key must exist before render:
     * Livewire refuses to entangle a property path (pilihan.{id}) that is not on the component yet.
     */
    public function mount(): void
    {
        $user = auth()->user();

        $this->pilihan = Kelas::query()
            ->unless($user->isSuperAdmin(), fn ($query) => $query->whereKey($user->kelas_id))
            ->pluck('semester_aktif_id', 'id')
            ->map(fn (int $semesterId) => (string) $semesterId)
            ->all();
    }

    #[Computed]
    public function semesterOptions(): Collection
    {
        return Semester::query()->orderBy('nomor')->get(['id', 'nomor', 'nama']);
    }

    #[Computed]
    public function daftarKelas(): LengthAwarePaginator
    {
        $user = auth()->user();

        return Kelas::query()
            ->select(['id', 'nama', 'prodi', 'angkatan', 'semester_aktif_id', 'updated_at'])
            ->with('semesterAktif:id,nama')
            ->withCount(['mahasiswa' => fn ($query) => $query->aktifDiSemesterKelas()])
            ->unless($user->isSuperAdmin(), fn ($query) => $query->whereKey($user->kelas_id))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('nama', 'like', '%'.trim($this->search).'%'))
            ->orderBy('nama')
            ->paginate($this->perPage());
    }

    public function simpan(int $kelasId, KelasContext $context): void
    {
        $kelas = Kelas::query()->findOrFail($kelasId);

        $this->authorize('updateSemester', $kelas);

        $semesterId = (int) ($this->pilihan[$kelasId] ?? 0);
        $semester = Semester::query()->find($semesterId);

        if ($semester === null) {
            $this->notify('Pilih semester yang valid.', 'error');

            return;
        }

        if ($kelas->semester_aktif_id === $semester->id) {
            $this->notify("Semester aktif {$kelas->nama} sudah {$semester->nama}.", 'warning');

            return;
        }

        $kelas->update(['semester_aktif_id' => $semester->id]);

        $context->refresh();

        // Full page refresh so the kelas/semester chip in the header reflects the change too.
        $this->flashNotify("Semester aktif {$kelas->nama} diubah ke {$semester->nama}.");
        $this->redirectRoute('semester-aktif.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.semester-aktif.index');
    }
}

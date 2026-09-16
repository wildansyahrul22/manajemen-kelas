<?php

namespace App\Livewire\LogAktivitas;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin kelas: the log of their own kelas. Super admin: every kelas, with a kelas filter, and may
 * delete entries (one at a time or everything matching the active filters).
 */
#[Title('Log Aktivitas')]
class Index extends Component
{
    use ManagesModalForm, Notifies, WithTableControls;

    public bool $confirmingDeleteFiltered = false;

    #[Url(as: 'kelas', except: '')]
    public string $kelasId = '';

    #[Url(as: 'aksi', except: '')]
    public string $aksi = '';

    #[Url(as: 'modul', except: '')]
    public string $modul = '';

    public function updatedKelasId(): void
    {
        $this->resetPage();
    }

    public function updatedAksi(): void
    {
        $this->resetPage();
    }

    public function updatedModul(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function actor(): User
    {
        return auth()->user();
    }

    /**
     * Kelas filter options (super admin only).
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function kelasOptions(): Collection
    {
        return $this->actor->isSuperAdmin()
            ? Kelas::query()->orderBy('nama')->pluck('nama', 'id')
            : collect();
    }

    /**
     * Entries visible to the actor, narrowed by the active filters.
     */
    protected function logQuery(): Builder
    {
        $actor = $this->actor;

        return ActivityLog::query()
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('kelas_id', $actor->kelas_id))
            ->when($actor->isSuperAdmin() && $this->kelasId !== '', fn ($query) => $query->where('kelas_id', (int) $this->kelasId))
            ->when(AksiLog::tryFrom($this->aksi), fn ($query, AksiLog $aksi) => $query->where('aksi', $aksi->value))
            ->when(ModulLog::tryFrom($this->modul), fn ($query, ModulLog $modul) => $query->where('modul', $modul->value))
            ->search($this->search);
    }

    #[Computed]
    public function daftarLog(): LengthAwarePaginator
    {
        return $this->logQuery()
            ->with(['user:id,name,role', 'kelas:id,nama'])
            ->latest('id')
            ->paginate($this->perPage());
    }

    /**
     * Human-readable summary of the active filters, used in the confirm dialog and the purge log entry.
     */
    #[Computed]
    public function ringkasanFilter(): string
    {
        $aksi = AksiLog::tryFrom($this->aksi);
        $modul = ModulLog::tryFrom($this->modul);

        $bagian = array_filter([
            $this->kelasId !== '' ? 'kelas '.($this->kelasOptions->get((int) $this->kelasId) ?? '?') : null,
            $aksi ? 'aksi '.$aksi->label() : null,
            $modul ? 'modul '.$modul->label() : null,
            trim($this->search) !== '' ? 'pencarian "'.trim($this->search).'"' : null,
        ]);

        return $bagian === [] ? 'semua entri' : implode(', ', $bagian);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', ActivityLog::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $log = ActivityLog::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $log);

        $log->delete();

        $this->closeDelete();
        unset($this->daftarLog);
        $this->notify('Entri log berhasil dihapus.');
    }

    public function confirmDeleteFiltered(): void
    {
        $this->authorize('deleteAny', ActivityLog::class);

        $this->confirmingDeleteFiltered = true;
    }

    /**
     * Remove every entry matching the active filters, then record the purge itself.
     */
    public function deleteFiltered(): void
    {
        $this->authorize('deleteAny', ActivityLog::class);

        $ringkasan = $this->ringkasanFilter;
        $jumlah = $this->logQuery()->delete();

        ActivityLog::catat(
            aksi: AksiLog::Hapus,
            modul: ModulLog::LogAktivitas,
            label: "{$jumlah} entri log ({$ringkasan})",
            kelasId: $this->kelasId !== '' ? (int) $this->kelasId : null,
        );

        $this->confirmingDeleteFiltered = false;
        $this->resetPage();
        unset($this->daftarLog);
        $this->notify("{$jumlah} entri log berhasil dihapus.");
    }

    public function render()
    {
        return view('livewire.log-aktivitas.index', [
            'aksiOptions' => AksiLog::options(),
            'modulOptions' => ModulLog::options(),
        ]);
    }
}

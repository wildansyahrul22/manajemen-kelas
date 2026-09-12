<?php

namespace App\Livewire\LogAktivitas;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Livewire\Concerns\WithTableControls;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin kelas: the log of their own kelas. Super admin: every kelas, with a kelas filter.
 */
#[Title('Log Aktivitas')]
class Index extends Component
{
    use WithTableControls;

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

    #[Computed]
    public function daftarLog(): LengthAwarePaginator
    {
        $actor = $this->actor;

        return ActivityLog::query()
            ->with(['user:id,name,role', 'kelas:id,nama'])
            ->when(! $actor->isSuperAdmin(), fn ($query) => $query->where('kelas_id', $actor->kelas_id))
            ->when($actor->isSuperAdmin() && $this->kelasId !== '', fn ($query) => $query->where('kelas_id', (int) $this->kelasId))
            ->when(AksiLog::tryFrom($this->aksi), fn ($query, AksiLog $aksi) => $query->where('aksi', $aksi->value))
            ->when(ModulLog::tryFrom($this->modul), fn ($query, ModulLog $modul) => $query->where('modul', $modul->value))
            ->search($this->search)
            ->latest('id')
            ->paginate($this->perPage());
    }

    public function render()
    {
        return view('livewire.log-aktivitas.index', [
            'aksiOptions' => AksiLog::options(),
            'modulOptions' => ModulLog::options(),
        ]);
    }
}

<?php

namespace App\Livewire\Users;

use App\Enums\Role;
use App\Livewire\Concerns\InteractsWithKelas;
use App\Livewire\Concerns\ManagesModalForm;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Concerns\WithTableControls;
use App\Livewire\Forms\UserForm;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\User;
use App\Support\ExcelExport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Users')]
class Index extends Component
{
    use InteractsWithKelas, ManagesModalForm, Notifies, WithTableControls;

    public UserForm $form;

    #[Url(except: '')]
    public string $role = '';

    /**
     * Kelas terbang users belong to the kelas in one semester only; by default the list shows the
     * roster of the active semester. Tick to also see kelas terbang users of other semesters.
     */
    #[Url(as: 'semua', except: false)]
    public bool $semuaSemester = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function updatedSemuaSemester(): void
    {
        $this->resetPage();
    }

    /**
     * Roles the acting user may assign.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function roleOptions(): array
    {
        $options = Role::options();

        if (! auth()->user()->isSuperAdmin()) {
            unset($options[Role::SuperAdmin->value]);
        }

        return $options;
    }

    #[Computed]
    public function kelasOptions(): Collection
    {
        return Kelas::query()->orderBy('nama')->get(['id', 'nama']);
    }

    #[Computed]
    public function semesterOptions(): Collection
    {
        return Semester::query()->orderBy('nomor')->get(['id', 'nama']);
    }

    /**
     * Super admin filtering on the super admin role sees every super admin (they have no kelas);
     * otherwise the list is the current kelas narrowed by role.
     */
    protected function showSuperAdmins(): bool
    {
        return auth()->user()->isSuperAdmin() && $this->role === Role::SuperAdmin->value;
    }

    protected function usersQuery(): Builder
    {
        $showSuperAdmins = $this->showSuperAdmins();

        return User::query()
            ->select(['id', 'npm', 'name', 'no_hp', 'role', 'kelas_id', 'kelas_terbang_semester_id', 'created_at'])
            ->with(['kelas:id,nama', 'semesterKelasTerbang:id,nama'])
            ->when($showSuperAdmins, fn ($query) => $query->where('role', Role::SuperAdmin))
            ->unless($showSuperAdmins, function ($query) {
                $query->forKelas($this->kelas->id)
                    ->unless($this->semuaSemester, fn ($query) => $query->aktifDiSemester($this->kelas->semester_aktif_id))
                    ->when($this->role !== '', fn ($query) => $query->where('role', $this->role));
            })
            ->search($this->search)
            ->orderBy('name');
    }

    #[Computed]
    public function daftarUsers(): LengthAwarePaginator
    {
        return $this->usersQuery()->paginate($this->perPage());
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $baris = $this->usersQuery()
            ->get()
            ->map(fn (User $user, int $indeks) => [
                $indeks + 1,
                $user->npm,
                $user->name,
                $user->noHpFormatted(),
                $user->role->label(),
                $user->kelas?->nama,
                $user->isKelasTerbang() ? 'Kelas terbang · '.$user->semesterKelasTerbang->nama : 'Reguler',
                $user->created_at,
            ]);

        return ExcelExport::buat('Users')
            ->subjudul($this->showSuperAdmins() ? 'Semua super admin' : 'Kelas '.$this->kelas->nama)
            ->filter([
                'Pencarian' => $this->search,
                'Role' => Role::tryFrom($this->role)?->label(),
                'Keanggotaan' => $this->showSuperAdmins() ? null : ($this->semuaSemester ? 'Termasuk kelas terbang semester lain' : 'Anggota '.$this->kelas->semesterAktif->nama),
            ])
            ->kolom(
                ['No', ExcelExport::TIPE_ANGKA],
                'NPM',
                'Nama',
                'No. HP',
                'Role',
                'Kelas',
                'Keanggotaan',
                ['Terdaftar Pada', ExcelExport::TIPE_WAKTU],
            )
            ->baris($baris)
            ->unduh('users '.($this->showSuperAdmins() ? 'super-admin' : $this->kelas->nama));
    }

    public function openCreate(): void
    {
        $this->authorize('create', User::class);

        $this->form->reset();
        $this->form->kelas_id = (string) $this->kelas->id;
        $this->openForm();
    }

    public function openEdit(int $id): void
    {
        $target = User::query()->findOrFail($id);

        $this->authorize('update', $target);

        $this->form->fillFrom($target);
        $this->openForm();
    }

    public function save(): void
    {
        $actor = auth()->user();
        $isEdit = $this->form->user !== null;

        $isEdit
            ? $this->authorize('update', $this->form->user)
            : $this->authorize('create', User::class);

        $user = $this->form->save(
            allowedRoles: array_keys($this->roleOptions),
            lockedKelasId: $actor->isSuperAdmin() ? null : $actor->kelas_id,
        );

        $this->closeForm();
        unset($this->daftarUsers);

        $pesan = $isEdit ? 'Data user berhasil diperbarui.' : 'User berhasil ditambahkan.';

        if ($user->isKelasTerbang() && ! $this->semuaSemester && $user->kelas_id === $this->kelas->id && ! $user->aktifPadaSemester($this->kelas->semester_aktif_id)) {
            $pesan .= ' Sebagai kelas terbang, user ini baru tampil saat '.$user->semesterKelasTerbang->nama.' menjadi semester aktif (centang "Tampilkan kelas terbang semester lain" untuk melihatnya sekarang).';
        }

        $this->notify($pesan);
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', User::query()->findOrFail($id));

        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $target = User::query()->findOrFail($this->deletingId);

        $this->authorize('delete', $target);

        $target->delete();

        $this->closeDelete();
        unset($this->daftarUsers);
        $this->notify('User berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}

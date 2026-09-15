<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
    }

    #[Computed]
    public function kelompok(): Collection
    {
        return $this->user->kelompok()
            ->select(['kelompok.id', 'kelompok.nama', 'kelompok.mata_kuliah_id'])
            ->with('mataKuliah:id,nama')
            ->orderBy('kelompok.nama')
            ->get();
    }

    public function render()
    {
        $this->user->loadMissing(['kelas:id,nama', 'semesterKelasTerbang:id,nama']);

        return view('livewire.users.show')->title($this->user->name);
    }
}

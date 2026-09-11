<?php

namespace App\Policies;

use App\Models\MataKuliah;
use App\Models\User;

class MataKuliahPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MataKuliah $mataKuliah): bool
    {
        return $user->belongsToKelas($mataKuliah->kelas_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, MataKuliah $mataKuliah): bool
    {
        return $user->canManageKelas($mataKuliah->kelas_id);
    }

    public function delete(User $user, MataKuliah $mataKuliah): bool
    {
        return $this->update($user, $mataKuliah);
    }
}

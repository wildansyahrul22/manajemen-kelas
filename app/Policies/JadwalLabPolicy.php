<?php

namespace App\Policies;

use App\Models\JadwalLab;
use App\Models\User;

class JadwalLabPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, JadwalLab $jadwalLab): bool
    {
        return $user->canManageKelas($jadwalLab->mataKuliah->kelas_id);
    }

    public function delete(User $user, JadwalLab $jadwalLab): bool
    {
        return $this->update($user, $jadwalLab);
    }
}

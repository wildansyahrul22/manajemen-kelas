<?php

namespace App\Policies;

use App\Models\JadwalKelas;
use App\Models\User;

class JadwalKelasPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, JadwalKelas $jadwal): bool
    {
        return $user->canManageKelas($jadwal->mataKuliah->kelas_id);
    }

    public function delete(User $user, JadwalKelas $jadwal): bool
    {
        return $this->update($user, $jadwal);
    }
}

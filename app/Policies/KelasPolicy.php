<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Change the active semester: admin for their own kelas, super admin for all.
     */
    public function updateSemester(User $user, Kelas $kelas): bool
    {
        return $user->canManageKelas($kelas->id);
    }
}

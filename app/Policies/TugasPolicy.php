<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\Tugas;
use App\Models\User;

class TugasPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tugas $tugas): bool
    {
        return $user->belongsToKelas($tugas->mataKuliah->kelas_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, Tugas $tugas): bool
    {
        return $user->canManageKelas($tugas->mataKuliah->kelas_id);
    }

    public function delete(User $user, Tugas $tugas): bool
    {
        return $this->update($user, $tugas);
    }

    /**
     * Super admin may always attach files; admin kelas only when the kelas is on a plan that
     * includes uploading (otherwise the tugas links to the files instead).
     */
    public function upload(User $user, Kelas $kelas): bool
    {
        return $user->isSuperAdmin() || ($kelas->bolehUpload() && $user->canManageKelas($kelas->id));
    }
}

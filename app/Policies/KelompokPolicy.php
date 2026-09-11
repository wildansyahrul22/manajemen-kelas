<?php

namespace App\Policies;

use App\Models\Kelompok;
use App\Models\User;

class KelompokPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Kelompok $kelompok): bool
    {
        return $user->belongsToKelas($kelompok->mataKuliah->kelas_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admin of the kelas may edit everything; mahasiswa only kelompok they created.
     */
    public function update(User $user, Kelompok $kelompok): bool
    {
        $kelasId = $kelompok->mataKuliah->kelas_id;

        return $user->canManageKelas($kelasId)
            || ($kelompok->created_by === $user->id && $user->belongsToKelas($kelasId));
    }

    public function delete(User $user, Kelompok $kelompok): bool
    {
        return $this->update($user, $kelompok);
    }
}

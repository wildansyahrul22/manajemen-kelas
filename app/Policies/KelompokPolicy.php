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
     * As long as the kategori is open every member of the kelas may arrange its kelompok; once it is
     * final nobody can, until an admin kelas unlocks the kategori again.
     */
    public function update(User $user, Kelompok $kelompok): bool
    {
        $kategori = $kelompok->kategori;

        return $kategori !== null
            && ! $kategori->isFinal()
            && $user->belongsToKelas($kelompok->mataKuliah->kelas_id);
    }

    public function delete(User $user, Kelompok $kelompok): bool
    {
        return $this->update($user, $kelompok);
    }
}

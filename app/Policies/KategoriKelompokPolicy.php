<?php

namespace App\Policies;

use App\Models\KategoriKelompok;
use App\Models\User;

class KategoriKelompokPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admin of the kelas may edit everything; mahasiswa only kategori they created.
     */
    public function update(User $user, KategoriKelompok $kategori): bool
    {
        $kelasId = $kategori->mataKuliah->kelas_id;

        return $user->canManageKelas($kelasId)
            || ($kategori->created_by === $user->id && $user->belongsToKelas($kelasId));
    }

    public function delete(User $user, KategoriKelompok $kategori): bool
    {
        return $this->update($user, $kategori);
    }
}
